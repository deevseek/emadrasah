import os
os.environ['FACE_API_TOKEN']='test-token'
from fastapi.testclient import TestClient
from app import main
from app.face_engine import FaceError
client=TestClient(main.app);auth={'Authorization':'Bearer test-token'}
class Engine:
 name='sface';version='test';ready=True
 def encode(self,image):
  if image.shape[0]==1:raise FaceError('NO_FACE_DETECTED','Tidak ada wajah terdeteksi.')
  return ([1.,0.] if image[0,0,0]>100 else [0.,1.]),.9
 def similarity(self,a,b):return sum(x*y for x,y in zip(a,b))
def image(value=255,size=2):
 import cv2,numpy as np
 return cv2.imencode('.jpg',np.full((size,size,3),value,np.uint8))[1].tobytes()
def setup_function():main.engine=Engine();main.load_error=None
def test_health():assert client.get('/health').json()['model_loaded'] is True
def test_invalid_auth():assert client.post('/v1/faces/encode',files={'image':('x.jpg',image())}).status_code==401
def test_invalid_image():assert client.post('/v1/faces/encode',headers=auth,files={'image':('x.jpg',b'bad')}).json()['error']['code']=='INVALID_IMAGE'
def test_no_face():assert client.post('/v1/faces/encode',headers=auth,files={'image':('x.jpg',image(size=1))}).json()['error']['code']=='NO_FACE_DETECTED'
def test_encode():assert client.post('/v1/faces/encode',headers=auth,files={'image':('x.jpg',image())}).json()['embedding']==[1.,0.]
def verify(value,ref):return client.post('/v1/faces/verify',headers=auth,files={'image':('x.jpg',image(value))},data={'reference_embeddings':str([ref]).replace("'",'"'),'threshold':'.8'}).json()
def test_verify_same_identity():assert verify(255,[1.,0.])['matched'] is True
def test_verify_different_identity():assert verify(0,[1.,0.])['matched'] is False
def test_model_unavailable():main.engine=None;assert client.post('/v1/faces/encode',headers=auth,files={'image':('x.jpg',image())}).status_code==503
def test_multiple_faces(monkeypatch):
 class Multiple(Engine):
  def encode(self,image):raise FaceError('MULTIPLE_FACES_DETECTED','Lebih dari satu wajah terdeteksi.')
 main.engine=Multiple();assert client.post('/v1/faces/encode',headers=auth,files={'image':('x.jpg',image())}).json()['error']['code']=='MULTIPLE_FACES_DETECTED'


def test_engine_retries_rotated_mobile_photo():
 from app.face_engine import SFaceEngine
 import numpy as np

 class Detector:
  def setInputSize(self,size):self.size=size
  def detect(self,image):
   if image.shape[:2]==(3,2):
    return None,np.asarray([[0,0,1,1,0,0,0,0,0,0,0,0,0,0,.9]],dtype=np.float32)
   return None,None

 engine=SFaceEngine.__new__(SFaceEngine);engine.detector=Detector()
 oriented,faces=engine._detect(np.zeros((2,3,3),dtype=np.uint8))
 assert oriented.shape[:2]==(3,2)
 assert len(faces)==1


def test_engine_downscales_high_resolution_mobile_photo(monkeypatch):
 from app import face_engine
 from app.face_engine import SFaceEngine
 import numpy as np

 monkeypatch.setattr(face_engine,'MAX_DETECTION_DIMENSION',1280)

 class Detector:
  def setInputSize(self,size):self.size=size
  def detect(self,image):
   assert max(image.shape[:2])==1280
   return None,np.asarray([[0,0,100,100,0,0,0,0,0,0,0,0,0,0,.9]],dtype=np.float32)

 engine=SFaceEngine.__new__(SFaceEngine);engine.detector=Detector()
 oriented,faces=engine._detect(np.zeros((3000,4000,3),dtype=np.uint8))
 assert oriented.shape[:2]==(960,1280)
 assert engine.detector.size==(1280,960)
 assert len(faces)==1
