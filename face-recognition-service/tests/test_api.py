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
  return ([1.,0.] if image[0,0,0]>100 else [0.,1.]),{'faces':1,'detector_confidence':.9,'face_area_ratio':.2,'brightness_score':128.,'blur_score':100.,'quality_score':.9}
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


def engine_with_face(face):
 from app.face_engine import SFaceEngine
 import numpy as np

 class Detector:
  def setInputSize(self,size):pass
  def detect(self,image):return None,np.asarray([face],dtype=np.float32)

 class Recognizer:
  def alignCrop(self,image,detected):return image
  def feature(self,image):return np.asarray([[3.,4.]],dtype=np.float32)

 engine=SFaceEngine.__new__(SFaceEngine)
 engine.detector=Detector();engine.recognizer=Recognizer()
 return engine


def test_engine_does_not_penalize_valid_face_area_twice(monkeypatch):
 from app import face_engine
 import numpy as np

 monkeypatch.setattr(face_engine,'MIN_FACE_AREA_RATIO',.025)
 monkeypatch.setattr(face_engine,'MIN_QUALITY',.35)
 monkeypatch.setattr(face_engine,'MIN_BRIGHTNESS',0)
 monkeypatch.setattr(face_engine,'MIN_BLUR_SCORE',0)
 # 4% of the image with 0.5 confidence failed the previous area × confidence
 # formula even though both signals independently meet their minimum.
 face=[0,0,20,20,0,0,0,0,0,0,0,0,0,0,.5]
 embedding,quality=engine_with_face(face).encode(np.zeros((100,100,3),dtype=np.uint8))
 assert quality['detector_confidence']==.5
 assert np.allclose(embedding,[.6,.8])


def test_engine_reports_face_that_is_too_far_from_camera(monkeypatch):
 from app import face_engine
 import numpy as np
 import pytest

 monkeypatch.setattr(face_engine,'MIN_FACE_AREA_RATIO',.025)
 face=[0,0,10,10,0,0,0,0,0,0,0,0,0,0,.9]
 with pytest.raises(FaceError,match='terlalu jauh') as error:
  engine_with_face(face).encode(np.zeros((100,100,3),dtype=np.uint8))
 assert error.value.code=='FACE_TOO_SMALL'

def burst(values, refs=([1., 0.],), threshold=.5):
 files=[('images',(f'{i}.jpg',image(value),'image/jpeg')) for i,value in enumerate(values)]
 return client.post('/v1/faces/verify-burst',headers=auth,files=files,data={'reference_embeddings':__import__('json').dumps(refs),'threshold':str(threshold)})

def test_burst_all_three_match():
 result=burst([255,255,255]).json()
 assert result['matched'] is True and result['matched_frames']==3 and result['confidence']==1.

def test_burst_two_of_three_match():
 result=burst([255,0,255]).json()
 assert result['matched'] is True and result['matched_frames']==2

def test_burst_one_of_three_does_not_match():
 result=burst([0,255,0]).json()
 assert result['matched'] is False and result['matched_frames']==1

def test_burst_bad_frame_is_ignored():
 result=burst([255,255,255,255],threshold=.5)
 assert result.json()['selected_frames']==3

def test_burst_only_one_valid_frame_is_rejected():
 response=burst([255],threshold=.5)
 assert response.status_code==422 and response.json()['error']['code']=='INSUFFICIENT_VALID_FRAMES'

def test_burst_empty_and_malformed_references():
 assert burst([255,255,255],refs=[]).json()['error']['code']=='EMPTY_REFERENCE_EMBEDDINGS'
 files=[('images',('1.jpg',image(),'image/jpeg'))]*3
 response=client.post('/v1/faces/verify-burst',headers=auth,files=files,data={'reference_embeddings':'{bad','threshold':'.5'})
 assert response.status_code==422 and response.json()['error']['code']=='INVALID_REFERENCE_EMBEDDING'
