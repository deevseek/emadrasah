import os
TOKEN=os.getenv('FACE_API_TOKEN','')
DETECTOR_MODEL=os.getenv('FACE_DETECTOR_MODEL','models/face_detection_yunet_2023mar.onnx')
RECOGNIZER_MODEL=os.getenv('FACE_RECOGNIZER_MODEL','models/face_recognition_sface_2021dec.onnx')
DETECTION_SCORE_THRESHOLD=float(os.getenv('FACE_DETECTION_SCORE_THRESHOLD','0.6'))
MAX_DETECTION_DIMENSION=int(os.getenv('FACE_MAX_DETECTION_DIMENSION','1280'))
MIN_QUALITY=float(os.getenv('MIN_FACE_QUALITY','0.45'))
