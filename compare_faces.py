import face_recognition
import os
import sys
import numpy as np

# Load known faces
known_encodings = []
known_names = []

for file in os.listdir("known_faces"):
    if file.lower().endswith(('.jpg', '.png')):
        img = face_recognition.load_image_file(f"known_faces/{file}")
        enc = face_recognition.face_encodings(img)
        if enc:
            known_encodings.append(enc[0])
            known_names.append(os.path.splitext(file)[0])

# Load webcam image
webcam_img = face_recognition.load_image_file(sys.argv[1])
webcam_enc = face_recognition.face_encodings(webcam_img)

if not webcam_enc:
    print("Face not detected on database.")
    sys.exit()

# Compare
distances = face_recognition.face_distance(known_encodings, webcam_enc[0])
min_index = np.argmin(distances)

if distances[min_index] < 0.45:
    print(f"{known_names[min_index]}")

else:
    print("Unknown face.")
