<?php
// upload_image.php - fungsi untuk mengunggah gambar dan mengatur ukurannya
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Fungsi untuk mengatur ukuran gambar
function resize_image($file_path, $target_width, $target_height) {
    list($width, $height) = getimagesize($file_path);
    $ratio = $width / $height;

    if ($width / $target_width > $height / $target_height) {
        $newwidth = $target_width;
        $newheight = $height * ($target_width / $width);
    } else {
        $newheight = $target_height;
        $newwidth = $width * ($target_height / $height);
    }

    $src = imagecreatetruecolor($newwidth, $newheight);
    $src = imagecopyresampled($src, imagecreatefromstring(file_get_contents($file_path)), 0, 0, $width, $height, $newwidth, $newheight);

    imagejpeg($src, $target_width . 'x' . $target_height . '.jpg', 90);
    imagedestroy($src);
}

// Proses upload gambar
if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../uploads/'; // Letak file diunggah di folder 'uploads'
    $file_tmp_name = $_FILES["file"]["tmp_name"];
    $file_name = $_FILES["file"]["name"];
    $file_ext = strtolower(end($file_name, '.'));
    $file_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $file_size = $_FILES["file"]["size"];
    $file_type = $_FILES["file"]["type"];

    // Validasi ekstensi dan ukuran file
    if (in_array($file_ext, $file_ext)) {
        if ($file_size <= 5000000) {
            $file_new_name = uniqid() . '.' . $file_ext;
            $file_destination = $upload_dir . $file_new_name;
            if (move_uploaded_file($file_tmp_name, $file_destination)) {
                // Resize image here
                resize_image($file_destination, 800, 600); // Contoh ukuran 800x600
                echo json_encode(['message' => 'File successfully uploaded', 'fileName' => $file_new_name]);
            } else {
                echo json_encode(['message' => 'Sorry, file upload failed.']);
            }
        } else {
            echo json_encode(['message' => 'Sorry, your file is too large.']);
        }
    } else {
        echo json_encode(['message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.']);
    }
} else {
    echo json_encode(['message' => 'Sorry, there was an error uploading your file.']);
}
?>