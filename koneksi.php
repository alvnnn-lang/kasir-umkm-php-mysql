<?php 
    $con = mysqli_connect("localhost", "root", "", "sistem_kasir_toko");

    if(mysqli_connect_errno()){
        echo "Koneksi database gagal: " . mysqli_connect_error();
        exit();
    }
?>
