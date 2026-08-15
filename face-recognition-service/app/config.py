import os
TOKEN=os.getenv('FACE_API_TOKEN','')
DETECTOR_MODEL=os.getenv('FACE_DETECTOR_MODEL','models/face_detection_yunet_2023mar.onnx')
RECOGNIZER_MODEL=os.getenv('FACE_RECOGNIZER_MODEL','models/face_recognition_sface_2021dec.onnx')
MIN_QUALITY=float(os.getenv('MIN_FACE_QUALITY','0.45'))
