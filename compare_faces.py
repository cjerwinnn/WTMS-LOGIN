import face_recognition
import os
import sys
import numpy as np

# Load known faces from subdirectories
known_encodings = []
known_names = []

# The main directory containing subfolders of known faces
known_faces_dir = "known_faces"

# Check if the known_faces directory exists
if not os.path.isdir(known_faces_dir):
    print(f"Error: Directory '{known_faces_dir}' not found.")
    sys.exit()

# Loop through each folder (each person) in the known_faces directory
for name in os.listdir(known_faces_dir):
    person_dir = os.path.join(known_faces_dir, name)

    # Check if it's a directory
    if os.path.isdir(person_dir):
        # Loop through each image file for that person
        for filename in os.listdir(person_dir):
            if filename.lower().endswith(('.jpg', '.png', '.jpeg')):
                # Load the image
                image_path = os.path.join(person_dir, filename)
                img = face_recognition.load_image_file(image_path)
                
                # Get face encodings
                encodings = face_recognition.face_encodings(img)

                # Add the first encoding found to our list of known encodings
                if encodings:
                    known_encodings.append(encodings[0])
                    # The name is the folder name (e.g., "PDMC000325")
                    known_names.append(name)

# --- The rest of the script remains the same ---

# Check if any known faces were loaded
if not known_encodings:
    print("No known faces found. Please check the 'known_faces' directory structure.")
    sys.exit()

# Load the webcam image from the command-line argument
webcam_img_path = sys.argv[1]
if not os.path.exists(webcam_img_path):
    print(f"Error: Webcam image not found at '{webcam_img_path}'")
    sys.exit()
    
webcam_img = face_recognition.load_image_file(webcam_img_path)
webcam_enc_list = face_recognition.face_encodings(webcam_img)

if not webcam_enc_list:
    print("Face not detected on database.")
    sys.exit()

webcam_enc = webcam_enc_list[0]

# Compare the webcam image to the known faces
distances = face_recognition.face_distance(known_encodings, webcam_enc)
min_index = np.argmin(distances)

# Set a tolerance for the match. Lower is more strict.
# You might need to adjust this value.
TOLERANCE = 0.45 

if distances[min_index] < TOLERANCE:
    # A match was found, print the name of the folder
    print(f"{known_names[min_index]}")
else:
    print("Unknown face.")