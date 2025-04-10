<?php
session_start();
echo json_encode(['count' => array_sum($_SESSION['cart'] ?? [])]);
