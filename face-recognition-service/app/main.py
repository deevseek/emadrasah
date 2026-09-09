import json
from statistics import median

import cv2
import numpy as np
from fastapi import Depends, FastAPI, File, Form, UploadFile
from fastapi.responses import JSONResponse

from .config import MAX_BURST_FRAMES, MAX_SELECTED_FRAMES, MIN_VALID_FRAMES
from .face_engine import FaceError, SFaceEngine
from .security import authorize

app = FastAPI(title='e-Madrasah Face Recognition', docs_url=None, redoc_url=None)
engine = None
load_error = None
try:
    engine = SFaceEngine()
except Exception as exc:
    load_error = str(exc)


def decode(data):
    image = cv2.imdecode(np.frombuffer(data, np.uint8), cv2.IMREAD_COLOR)
    if image is None:
        raise FaceError('INVALID_IMAGE', 'Berkas gambar tidak valid.')
    return image


def references(raw):
    try:
        values = json.loads(raw)
    except (TypeError, json.JSONDecodeError):
        raise FaceError('INVALID_REFERENCE_EMBEDDING', 'Data referensi wajah tidak valid.')
    if not isinstance(values, list) or not values:
        raise FaceError('EMPTY_REFERENCE_EMBEDDINGS', 'Wajah belum memiliki sampel referensi.')
    return values


@app.exception_handler(FaceError)
def face_error(_, exc):
    return JSONResponse(status_code=422, content={'success': False, 'error': {'code': exc.code, 'message': exc.message}})


@app.get('/health')
def health():
    return {'status': 'ok' if engine else 'unavailable', 'engine': 'sface', 'model_loaded': bool(engine), 'detail': load_error}


@app.post('/v1/faces/encode', dependencies=[Depends(authorize)])
async def encode(image: UploadFile = File(...)):
    if not engine:
        return JSONResponse(status_code=503, content={'error': {'code': 'MODEL_UNAVAILABLE', 'message': 'Model wajah belum siap.'}})
    embedding, metadata = engine.encode(decode(await image.read()))
    return {'success': True, **metadata, 'embedding': embedding, 'model': 'sface', 'model_version': engine.version}


@app.post('/v1/faces/verify', dependencies=[Depends(authorize)])
async def verify(image: UploadFile = File(...), reference_embeddings: str = Form(...), threshold: float = Form(...)):
    """Endpoint lama tetap tersedia untuk klien yang belum mendukung burst."""
    if not engine:
        return JSONResponse(status_code=503, content={'error': {'code': 'MODEL_UNAVAILABLE', 'message': 'Model wajah belum siap.'}})
    live, _ = engine.encode(decode(await image.read()))
    confidence = max(engine.similarity(live, reference) for reference in references(reference_embeddings))
    return {'success': True, 'matched': confidence >= threshold, 'confidence': confidence, 'faces': 1,
            'liveness_passed': None, 'liveness_supported': False}


@app.post('/v1/faces/verify-burst', dependencies=[Depends(authorize)])
async def verify_burst(images: list[UploadFile] = File(...), reference_embeddings: str = Form(...), threshold: float = Form(...)):
    if not engine:
        return JSONResponse(status_code=503, content={'error': {'code': 'MODEL_UNAVAILABLE', 'message': 'Model wajah belum siap.'}})
    if not 1 <= len(images) <= MAX_BURST_FRAMES:
        raise FaceError('INVALID_FRAME_COUNT', f'Kirim antara 1 dan {MAX_BURST_FRAMES} frame.')
    refs = references(reference_embeddings)
    valid, rejected = [], []
    for index, upload in enumerate(images):
        try:
            embedding, quality = engine.encode(decode(await upload.read()))
            valid.append({'index': index, 'embedding': embedding, **quality})
        except FaceError as exc:
            rejected.append({'index': index, 'code': exc.code})
    valid.sort(key=lambda frame: frame['quality_score'], reverse=True)
    selected = valid[:MAX_SELECTED_FRAMES]
    if len(selected) < MIN_VALID_FRAMES:
        code = rejected[0]['code'] if rejected and not selected else 'INSUFFICIENT_VALID_FRAMES'
        messages = {
            'FACE_TOO_SMALL': 'Wajah terlalu jauh dari kamera.',
            'FACE_TOO_DARK': 'Pencahayaan terlalu gelap.',
            'FACE_TOO_BRIGHT': 'Pencahayaan terlalu terang.',
            'FACE_TOO_BLURRY': 'Foto masih buram. Tahan perangkat sebentar.',
        }
        raise FaceError('INSUFFICIENT_VALID_FRAMES', messages.get(code, 'Wajah belum terlihat cukup jelas. Hadap ke kamera dan jangan bergerak sebentar.'))
    for frame in selected:
        frame['confidence'] = max(engine.similarity(frame['embedding'], reference) for reference in refs)
        del frame['embedding']
        frame['matched'] = frame['confidence'] >= threshold
    confidences = [frame['confidence'] for frame in selected]
    matched_frames = sum(frame['matched'] for frame in selected)
    required_matches = 2 if len(selected) >= 3 else len(selected)
    matched = matched_frames >= required_matches
    best = max(selected, key=lambda frame: (frame['matched'], frame['quality_score'] + frame['confidence']))
    return {
        'success': True, 'matched': matched, 'confidence': float(median(confidences)), 'faces': 1,
        'valid_frames': len(valid), 'selected_frames': len(selected), 'matched_frames': matched_frames,
        'frame_confidences': [frame['confidence'] for frame in selected], 'best_frame_index': best['index'],
        'rejected_frames': rejected, 'liveness_passed': None, 'liveness_supported': False,
    }
