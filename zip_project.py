import os
import zipfile

def zip_dir(dir_path, zip_path):
    # Exclude these directories
    excludes = {'node_modules', '.git', 'dist'}
    
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(dir_path):
            # modify dirs in-place to skip excluded directories
            dirs[:] = [d for d in dirs if d not in excludes]
            
            for file in files:
                file_path = os.path.join(root, file)
                # Ensure we don't zip the zip file itself or python script
                if file == 'kutch-safari-resort-source.zip' or file == 'zip_project.py':
                    continue
                    
                arcname = os.path.relpath(file_path, start=dir_path)
                zipf.write(file_path, arcname)

if __name__ == "__main__":
    src_dir = "C:\\Users\\ADMIN\\Downloads\\kutch-safari-resort"
    out_zip = "C:\\Users\\ADMIN\\Downloads\\kutch-safari-resort-source.zip"
    print(f"Creating zip file {out_zip} ...")
    zip_dir(src_dir, out_zip)
    size_mb = os.path.getsize(out_zip) / (1024 * 1024)
    print(f"Done! Zip size is {size_mb:.2f} MB")
