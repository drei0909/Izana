<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer List | Izana Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Quicksand', sans-serif;
      background: #e0e0e0;
      color: #2b2b2b;
      margin: 0;
      height: 100vh;
      overflow: hidden;
    }
    .wrapper {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }
    .sidebar {
      width: 250px;
      background: #1c1c1c;
      color: #fff;
      flex-shrink: 0;
      overflow-y: auto;
    }
    .sidebar .nav-link {
      color: #bdbdbd;
      font-weight: 500;
      margin-bottom: 10px;
      padding: 10px 15px;
      border-radius: 12px;
      transition: all 0.3s ease;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background-color: #6f4e37;
      color: #fff;
      transform: translateX(6px);
    }
    .main {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .admin-header {
      background: #f4f4f4;
      padding: 15px 25px;
      border-bottom: 1px solid #d6d6d6;
      box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .dashboard-content {
      flex-grow: 1;
      overflow-y: auto;
      padding: 20px;
    }
    .section-title {
      border-left: 6px solid #6f4e37;
      padding-left: 12px;
      margin: 30px 0 20px;
      font-weight: 700;
      color: #333;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .card {
      border: none;
      border-radius: 15px;
      background: #f4f4f4;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .table th {
      background: #6f4e37;
      color: #fff;
    }
    .pagination .page-item .page-link {
      border-radius: 10px;
      margin: 0 3px;
      color: #1c1c1c;
      border: none;
      font-weight: 600;
    }
    .pagination .page-item.active .page-link {
      background-color: #6f4e37;
      color: #fff;
    }
    .pagination .page-item .page-link:hover {
      background-color: #333;
      color: #fff;
    }
    @media (max-width: 992px) {
      .sidebar {
        position: fixed;
        height: 100%;
        z-index: 1000;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      .sidebar.show {
        transform: translateX(0);
      }
      .toggle-btn {
        display: inline-block;
        cursor: pointer;
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>