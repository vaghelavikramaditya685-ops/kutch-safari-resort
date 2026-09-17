import os
import zipfile

def zip_dir(dir_path, zip_path):
    # Exclude these directories completely
    excludes = {'node_modules', '.git', 'dist'}
    # Also exclude the heavy media folder
    exclude_paths = [os.path.join(dir_path, 'client', 'public', 'assets', 'images')]
    
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(dir_path):
            # modify dirs in-place to skip excluded directories
            dirs[:] = [d for d in dirs if d not in excludes]
            
            # Skip if we are inside the excluded images directory
            skip = False
            for ep in exclude_paths:
                if root.startswith(ep):
                    skip = True
                    break
            if skip:
                continue
            
            for file in files:
                file_path = os.path.join(root, file)
                
                # Exclude any previous zip files or mp4/mov if they sneaked in
                if file.endswith('.zip') or file.endswith('.mp4') or file.endswith('.mov'):
                    continue
                    
                arcname = os.path.relpath(file_path, start=dir_path)
                zipf.write(file_path, arcname)

if __name__ == "__main__":
    src_dir = "C:\\Users\\ADMIN\\Downloads\\kutch-safari-resort"
    out_zip = "C:\\Users\\ADMIN\\Downloads\\kutch-safari-resort-email-ready-code.zip"
    print(f"Creating zip file {out_zip} ...")
    zip_dir(src_dir, out_zip)
    size_mb = os.path.getsize(out_zip) / (1024 * 1024)
    print(f"Done! Zip size is {size_mb:.2f} MB")
