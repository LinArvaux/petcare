import os
import pandas as pd

# Anchor the paths to the ml/ folder where this script lives
script_dir = os.path.dirname(os.path.abspath(__file__))
# Navigate up one level to find the dataset folder
dataset_dir = os.path.abspath(os.path.join(script_dir, '../dataset'))
data = []

# 1. Process the Healthy folder (reads from both cat and dog subfolders)
healthy_dir = os.path.join(dataset_dir, 'Healthy')
count_healthy = 0
for species in ['cat', 'dog']:
    species_dir = os.path.join(healthy_dir, species)
    if os.path.exists(species_dir):
        for filename in os.listdir(species_dir):
            if filename.lower().endswith(('.jpg', '.jpeg', '.png')):
                img_path = os.path.join(species_dir, filename)
                data.append([img_path, 'Healthy'])
                count_healthy += 1
print(f" -> Found {count_healthy} images for: Healthy (Cats & Dogs)")

# 2. Process the Diseased folder (reads diseases inside cat and dog subfolders)
diseased_dir = os.path.join(dataset_dir, 'Diseased')
for species in ['cat', 'dog']:
    species_dir = os.path.join(diseased_dir, species)
    if os.path.exists(species_dir):
        diseases = [d for d in os.listdir(species_dir) if os.path.isdir(os.path.join(species_dir, d))]
        print(f"Detected {species} disease classes: {diseases}")
        
        for label in diseases:
            folder_path = os.path.join(species_dir, label)
            count = 0
            for filename in os.listdir(folder_path):
                if filename.lower().endswith(('.jpg', '.jpeg', '.png')):
                    img_path = os.path.join(folder_path, filename)
                    data.append([img_path, label])
                    count += 1
            print(f" -> Found {count} images for: {species}/{label}")
# 3. Save to CSV
if not data:
    print("\nERROR: No images were found.")
else:
    df = pd.DataFrame(data, columns=['filename', 'label'])
    # Force the CSV to save inside the ml/ folder
    csv_path = os.path.join(script_dir, 'multiclass_annotations.csv')
    df.to_csv(csv_path, index=False)
    print(f"\nSuccessfully created multiclass_annotations.csv with {len(data)} total images.")