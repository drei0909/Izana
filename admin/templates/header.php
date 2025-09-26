<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer List | Izana Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap 5.3.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- DataTables Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

  <style>
      body {
          font-family: 'Quicksand', sans-serif;
          background: #e0e0e0;
          color: #2b2b2b;
          margin: 0;
          height: 100vh;
          overflow-x: hidden;
      }
      .wrapper {
          display: flex;
          height: 100vh;
          overflow: hidden;
      }
      .sidebar {
          width: 250px;
          flex-shrink: 0;
          background: #1c1c1c;
          color: #fff;
          box-shadow: 3px 0 12px rgba(0,0,0,0.25);
          display: flex;
          flex-direction: column;
          position: fixed;
          top: 0;
          bottom: 0;
          left: 0;
          overflow-y: auto;
      }
      .main {
          margin-left: 250px;
          flex-grow: 1;
          display: flex;
          flex-direction: column;
          height: 100vh;
          overflow: hidden;
      }
      .content {
          flex-grow: 1;
          overflow-y: auto;
          padding: 20px;
      }
      .sidebar .nav-link {
          color: #bdbdbd;
          font-weight: 500;
          margin-bottom: 10px;
          padding: 10px 15px;
          border-radius: 12px;
          transition: all 0.3s ease;
      }
      .sidebar .nav-link.active, .sidebar .nav-link:hover {
          background-color: #6f4e37;
          color: #fff;
          transform: translateX(6px);
      }
      .admin-header {
          background: #f4f4f4;
          padding: 15px 25px;
          border-bottom: 1px solid #d6d6d6;
          box-shadow: 0 2px 6px rgba(0,0,0,0.08);
          flex-shrink: 0;
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
      .table {
          background: #fff;
          border-radius: 12px;
          overflow: hidden;
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }
      .table thead {
          background: #6f4e37;
          color: #fff;
      }
      .table tbody tr:hover {
          background: #f8f1ed;
      }
      /* Coffee style pagination */
.dataTables_wrapper .dataTables_paginate .paginate_button {
  border-radius: 50% !important;
  margin: 0 5px;
  color: #1c1c1c !important;
  background-color: #f4f4f4 !important;
  border: none !important;
  font-weight: bold;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
  background-color: #6f4e37 !important; /* Coffee brown */
  color: #fff !important;
}

  </style>
</head>
<body>

  <!-- jQuery (needed for DataTables) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Bootstrap 5 Bundle (includes Popper.js) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
