import cv2,numpy as np
from .config import DETECTOR_MODEL,RECOGNIZER_MODEL,MIN_QUALITY
class FaceError(Exception):
 def __init__(self,code,message):self.code,self.message=code,message
class SFaceEngine:
 name='sface';version='opencv-sface-2021dec'
 def __init__(self):
  self.detector=cv2.FaceDetectorYN.create(DETECTOR_MODEL,'',(320,320),score_threshold=.8,nms_threshold=.3,top_k=20)
  self.recognizer=cv2.FaceRecognizerSF.create(RECOGNIZER_MODEL,'')
 @property
 def ready(self):return self.detector is not None and self.recognizer is not None
 def encode(self,image):
  h,w=image.shape[:2];self.detector.setInputSize((w,h));_,faces=self.detector.detect(image);count=0 if faces is None else len(faces)
  if count==0:raise FaceError('NO_FACE_DETECTED','Tidak ada wajah terdeteksi.')
  if count>1:raise FaceError('MULTIPLE_FACES_DETECTED','Lebih dari satu wajah terdeteksi.')
  face=faces[0];quality=float(min(1,(face[2]*face[3])/(w*h)*5)*face[-1])
  if quality<MIN_QUALITY:raise FaceError('FACE_QUALITY_TOO_LOW','Kualitas wajah terlalu rendah.')
  aligned=self.recognizer.alignCrop(image,face);embedding=self.recognizer.feature(aligned).flatten();embedding/=np.linalg.norm(embedding)
  return embedding.tolist(),quality
 def similarity(self,a,b):return float(np.dot(np.asarray(a,dtype=np.float32),np.asarray(b,dtype=np.float32)))
