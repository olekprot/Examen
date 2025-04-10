<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Management System (MySQLi Procedural)</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin=""/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
  <?php
  // Database configuration
  


  // Set character set to UTF-8
  if (!mysqli_set_charset($conn, "utf8mb4")) {
      printf("Error loading character set utf8mb4: %s\n", mysqli_error($conn));
      // Consider dying here if charset is critical
  }


  // Get the current view mode (list, grid, table, map, calendar)
  $view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';


  // Get search query if present
  $search_query = isset($_GET['search']) ? trim($_GET['search']) : ''; // Trim whitespace


  // Get category filter if present
  $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;


  // Get event ID for detail view
  $event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;


  // Fetch categories for filter using mysqli_query
  $categories = [];
  $sql_categories = "SELECT id, name FROM categories ORDER BY name";
  $result_categories = mysqli_query($conn, $sql_categories);


  if ($result_categories) {
      $categories = mysqli_fetch_all($result_categories, MYSQLI_ASSOC);
      mysqli_free_result($result_categories); // Free result set
  } else {
      echo "Error fetching categories: " . mysqli_error($conn);
      // Decide if you want to die() here or continue without categories
  }


  // Base query for events
  $query = "SELECT e.*, c.name as category_name
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            WHERE 1";
  $params = [];
  $types = ""; // String for parameter types (i=integer, s=string, d=double, b=blob)


  // Add search condition if search query is provided
  if (!empty($search_query)) {
      $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
      $search_param = "%" . $search_query . "%";
      $params[] = $search_param; // Add param for title
      $params[] = $search_param; // Add param for description
      $types .= "ss"; // Two string parameters
  }


  // Add category filter if selected
  if ($category_filter > 0) {
      $query .= " AND e.category_id = ?";
      $params[] = $category_filter; // Add category ID param
      $types .= "i"; // One integer parameter
  }


  // Add order by date
  $query .= " ORDER BY e.event_date";


  // Prepare and execute query for events using mysqli prepared statements
  $events = [];
  $stmt = mysqli_prepare($conn, $query);


  if ($stmt) {
      // Bind parameters if any exist
      if (!empty($params)) {
          // Use the splat operator (...) to pass array elements as individual arguments
          if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
              echo "Error binding parameters: " . mysqli_stmt_error($stmt);
              die();
          }
      }


      // Execute the statement
      if (mysqli_stmt_execute($stmt)) {
          // Get the result set
          $result_events = mysqli_stmt_get_result($stmt);
          if ($result_events) {
              // Fetch all results
              $events = mysqli_fetch_all($result_events, MYSQLI_ASSOC);
              // Result object is implicitly freed when statement is closed
          } else {
              echo "Error getting result set: " . mysqli_stmt_error($stmt);
          }
      } else {
          echo "Error executing statement: " . mysqli_stmt_error($stmt);
      }


      // Close the statement
      mysqli_stmt_close($stmt);
  } else {
      echo "Error preparing statement: " . mysqli_error($conn);
      die(); // Critical error if statement can't be prepared
  }
  ?>


  <div class="container mx-auto px-4 py-8">
      <header class="mb-8">
          <h1 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">Event Management System (MySQLi Procedural)</h1>


          <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0 mb-6">
              <form action="" method="GET" class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4">
                  <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_mode); ?>">
                  <div class="flex-grow">
                      <input type="text" name="search" placeholder="Search events..."
                             value="<?php echo htmlspecialchars($search_query); ?>"
                             class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base">
                  </div>
                  <div class="w-full md:w-auto">
                      <select name="category" class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-base">
                          <option value="0">All Categories</option>
                          <?php foreach ($categories as $category): ?>
                              <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($category['name']); ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  <div>
                      <button type="submit" class="w-full md:w-auto px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                          Search
                      </button>
                  </div>
              </form>


              <div class="flex space-x-2">
                  <?php
                      // Helper function to build query string for view links
                      function build_view_link_params($current_search, $current_category) {
                          $link_params = '';
                          if (!empty($current_search)) {
                              $link_params .= '&search='.urlencode($current_search);
                          }
                          if ($current_category > 0) {
                              $link_params .= '&category='.$current_category;
                          }
                          return $link_params;
                      }
                      $link_extra_params = build_view_link_params($search_query, $category_filter);
                  ?>
                  <a href="?view=list<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'list' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      List
                  </a>
                  <a href="?view=grid<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'grid' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Grid
                  </a>
                  <a href="?view=table<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'table' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Table
                  </a>
                  <a href="?view=map<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'map' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Map
                  </a>
                  <a href="?view=calendar<?php echo $link_extra_params; ?>"
                     class="px-4 py-2 rounded-lg <?php echo $view_mode == 'calendar' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                      Calendar
                  </a>
              </div>
          </div>
      </header>