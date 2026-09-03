import cv2
import numpy as np

from .config import DETECTOR_MODEL, MIN_QUALITY, RECOGNIZER_MODEL


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
            score_threshold=.8,
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
            height, width = candidate.shape[:2]
            self.detector.setInputSize((width, height))
            _, faces = self.detector.detect(candidate)
            if faces is not None and len(faces) > 0:
                return candidate, faces

        return image, None

    def encode(self, image):
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
        quality = float(min(1, (face[2] * face[3]) / (width * height) * 5) * face[-1])
        if quality < MIN_QUALITY:
            raise FaceError('FACE_QUALITY_TOO_LOW', 'Kualitas wajah terlalu rendah.')

        aligned = self.recognizer.alignCrop(image, face)
        embedding = self.recognizer.feature(aligned).flatten()
        embedding /= np.linalg.norm(embedding)

        return embedding.tolist(), quality

    def similarity(self, a, b):
        return float(np.dot(np.asarray(a, dtype=np.float32), np.asarray(b, dtype=np.float32)))
