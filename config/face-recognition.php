<?php
return ['driver'=>env('FACE_RECOGNITION_DRIVER'),'url'=>rtrim((string)env('FACE_RECOGNITION_URL',''),'/'),'token'=>env('FACE_RECOGNITION_TOKEN'),'timeout'=>(int)env('FACE_RECOGNITION_TIMEOUT',10)];
