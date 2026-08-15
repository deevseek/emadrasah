import json,cv2,numpy as np
from fastapi import FastAPI,UploadFile,File,Form,Depends
from fastapi.responses import JSONResponse
from .security import authorize
from .face_engine import SFaceEngine,FaceError
app=FastAPI(title='e-Madrasah Face Recognition',docs_url=None,redoc_url=None);engine=None;load_error=None
try:engine=SFaceEngine()
except Exception as exc:load_error=str(exc)
def decode(data):
 image=cv2.imdecode(np.frombuffer(data,np.uint8),cv2.IMREAD_COLOR)
 if image is None:raise FaceError('INVALID_IMAGE','Berkas gambar tidak valid.')
 return image
@app.exception_handler(FaceError)
def face_error(_,exc):return JSONResponse(status_code=422,content={'success':False,'error':{'code':exc.code,'message':exc.message}})
@app.get('/health')
def health():return {'status':'ok' if engine else 'unavailable','engine':'sface','model_loaded':bool(engine),'detail':load_error}
@app.post('/v1/faces/encode',dependencies=[Depends(authorize)])
async def encode(image:UploadFile=File(...)):
 if not engine:return JSONResponse(status_code=503,content={'error':{'code':'MODEL_UNAVAILABLE','message':'Model wajah belum siap.'}})
 embedding,quality=engine.encode(decode(await image.read()));return {'success':True,'faces':1,'embedding':embedding,'quality_score':quality,'model':'sface','model_version':engine.version}
@app.post('/v1/faces/verify',dependencies=[Depends(authorize)])
async def verify(image:UploadFile=File(...),reference_embeddings:str=Form(...),threshold:float=Form(...)):
 if not engine:return JSONResponse(status_code=503,content={'error':{'code':'MODEL_UNAVAILABLE','message':'Model wajah belum siap.'}})
 live,_=engine.encode(decode(await image.read()));refs=json.loads(reference_embeddings);confidence=max(engine.similarity(live,r) for r in refs);return {'success':True,'matched':confidence>=threshold,'confidence':confidence,'faces':1,'liveness_passed':None,'liveness_supported':False}
