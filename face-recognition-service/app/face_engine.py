import cv2
import numpy as np

from .config import (
    DETECTION_SCORE_THRESHOLD,
    DETECTOR_MODEL,
    MAX_DETECTION_DIMENSION,
    MIN_FACE_AREA_RATIO,
    MIN_BRIGHTNESS,
    MAX_BRIGHTNESS,
    MIN_BLUR_SCORE,
    MIN_QUALITY,
    RECOGNIZER_MODEL,
)


class FaceError(Exception):
    def __init__(self, code, message):
        self.code, self.message = code, message


class SFaceEngine:
    name = 'sface'
    version = 'opencv-sface-2021dec'

    def __init__(self):
        self.detector = cv2.FaceDetectorYN.create(
            DETECTOR_MODEL,
            '',
            (320, 320),
            # YuNet's confidence can drop noticeably on mobile cameras because of
            # compression, backlighting, and slight blur. 0.8 rejected otherwise
            # usable attendance photos before the quality check could run.
            score_threshold=DETECTION_SCORE_THRESHOLD,
            nms_threshold=.3,
            top_k=20,
        )
        self.recognizer = cv2.FaceRecognizerSF.create(RECOGNIZER_MODEL, '')

    @property
    def ready(self):
        return self.detector is not None and self.recognizer is not None

    def _detect(self, image):
        """Detect a face, including in mobile photos whose orientation was lost."""
        candidates = (
            image,
            cv2.rotate(image, cv2.ROTATE_90_CLOCKWISE),
            cv2.rotate(image, cv2.ROTATE_90_COUNTERCLOCKWISE),
            cv2.rotate(image, cv2.ROTATE_180),
        )

        for candidate in candidates:
            candidate = self._limit_detection_size(candidate)
            height, width = candidate.shape[:2]
            self.detector.setInputSize((width, height))
            _, faces = self.detector.detect(candidate)
            if faces is not None and len(faces) > 0:
                return candidate, faces

        return image, None

    @staticmethod
    def _limit_detection_size(image):
        """Keep YuNet input in a reliable range without changing its aspect ratio."""
        height, width = image.shape[:2]
        longest_side = max(height, width)
        if longest_side <= MAX_DETECTION_DIMENSION:
            return image

        scale = MAX_DETECTION_DIMENSION / longest_side
        return cv2.resize(
            image,
            (max(1, round(width * scale)), max(1, round(height * scale))),
            interpolation=cv2.INTER_AREA,
        )

    def analyze(self, image):
        image, faces = self._detect(image)
        count = 0 if faces is None else len(faces)
        if count == 0:
            raise FaceError(
                'NO_FACE_DETECTED',
                'Wajah tidak terdeteksi pada foto terbaru. Pastikan wajah terlihat jelas dan menghadap kamera.',
            )
        if count > 1:
            raise FaceError('MULTIPLE_FACES_DETECTED', 'Lebih dari satu wajah terdeteksi.')

        height, width = image.shape[:2]
        face = faces[0]
        face_area_ratio = float((face[2] * face[3]) / (width * height))
        if face_area_ratio < MIN_FACE_AREA_RATIO:
            raise FaceError(
                'FACE_TOO_SMALL',
                'Wajah terlalu jauh dari kamera. Dekatkan kamera hingga wajah memenuhi bingkai foto.',
            )

        # YuNet's detector confidence is a more stable quality gate than the old
        # confidence × face-area formula. The old formula penalised a valid face
        # twice when a head covering lowered the detector confidence and made the
        # detected box slightly smaller. Face size now has its own explicit gate.
        quality = float(face[-1])
        if quality < MIN_QUALITY:
            raise FaceError(
                'FACE_QUALITY_TOO_LOW',
                'Wajah belum terlihat cukup jelas. Hadap ke kamera, tambah pencahayaan, dan hindari foto buram.',
            )

        x, y, w, h = [max(0, int(value)) for value in face[:4]]
        crop = image[y:min(height, y + h), x:min(width, x + w)]
        if crop.size == 0:
            raise FaceError('FACE_QUALITY_TOO_LOW', 'Wajah belum terlihat cukup jelas. Silakan coba lagi.')
        gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
        brightness = float(np.mean(gray))
        blur = float(cv2.Laplacian(gray, cv2.CV_64F).var())
        if brightness < MIN_BRIGHTNESS:
            raise FaceError('FACE_TOO_DARK', 'Pencahayaan terlalu gelap. Hadapkan wajah ke sumber cahaya.')
        if brightness > MAX_BRIGHTNESS:
            raise FaceError('FACE_TOO_BRIGHT', 'Pencahayaan terlalu terang. Hindari cahaya langsung ke kamera.')
        if blur < MIN_BLUR_SCORE:
            raise FaceError('FACE_TOO_BLURRY', 'Foto masih buram. Tahan perangkat sebentar.')

        metadata = {
            'faces': 1,
            'detector_confidence': quality,
            'face_area_ratio': face_area_ratio,
            'brightness_score': brightness,
            'blur_score': blur,
            'quality_score': self._quality_score(quality, face_area_ratio, brightness, blur),
        }
        return image, face, metadata

    @staticmethod
    def _quality_score(confidence, area_ratio, brightness, blur):
        brightness_score = max(0.0, 1.0 - abs(brightness - 130.0) / 130.0)
        area_score = min(1.0, area_ratio / 0.12)
        blur_score = min(1.0, blur / 150.0)
        return float(.45 * confidence + .20 * area_score + .20 * blur_score + .15 * brightness_score)

    def encode(self, image):
        image, face, metadata = self.analyze(image)
        aligned = self.recognizer.alignCrop(image, face)
        embedding = self.recognizer.feature(aligned).flatten()
        norm = float(np.linalg.norm(embedding))
        if norm <= 0 or not np.isfinite(norm) or not np.all(np.isfinite(embedding)):
            raise FaceError('INVALID_EMBEDDING', 'Data wajah tidak dapat diproses. Silakan ambil ulang foto.')
        embedding = embedding / norm

        return embedding.tolist(), metadata

    def similarity(self, a, b):
        left, right = np.asarray(a, dtype=np.float32), np.asarray(b, dtype=np.float32)
        if left.ndim != 1 or right.ndim != 1 or left.size == 0 or left.shape != right.shape:
            raise FaceError('INVALID_REFERENCE_EMBEDDING', 'Data referensi wajah tidak valid.')
        if not np.all(np.isfinite(left)) or not np.all(np.isfinite(right)):
            raise FaceError('INVALID_REFERENCE_EMBEDDING', 'Data referensi wajah tidak valid.')
        left_norm, right_norm = np.linalg.norm(left), np.linalg.norm(right)
        if left_norm <= 0 or right_norm <= 0:
            raise FaceError('INVALID_REFERENCE_EMBEDDING', 'Data referensi wajah tidak valid.')
        return float(np.dot(left / left_norm, right / right_norm))
