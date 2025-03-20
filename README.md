# Snappy Shopper Technical Test

In the task set, I developed a standalone Laravel-based PHP application designed to manage stores, their locations, and relationships with UK postcodes, exposed through a JSON API suitable specifically for integration with mobile applications.

Overall, I spent around 5 hours on the task. I could have spent less, but I wanted to make sure it was an accurate reflection of my skillset, and used it as
an opportunity to try and show off technically. On the other hand, I could have spent longer on the task, and as such I have attached a section to the bottom of the `README.md` with
thoughts for the future.

## Features Implemented

-   **Console Command for UK Postcodes Import:**\
    I implemented an Artisan command to download and import UK postcode data directly into the database. I chose to use Laravel's built-in chunking methods to handle large datasets effectively, intentionally avoiding third-party dependencies to keep the solution straightforward and maintainable.

  
- **Store Creation Endpoint:**\
    I structured this clearly through multiple layers:

    -   **DTOs** (Data Transfer Objects) to ensure clear and structured data flow.
    -   **Form Requests** to provide robust input validation.
    -   **Service Layer** to encapsulate the core business logic in one easily testable place.
    -   **Repository Pattern** to abstract and isolate database interactions.
    -   **Controller Actions** to coordinate these components, handle exceptions gracefully, and log errors through injected, PSR-compliant logging.

  
- **Endpoints for Nearby and Deliverable Stores:**\
    Included advanced geographic querying capabilities by first calculating a bounding box for efficiency, followed by precise distance calculations using the Haversine formula.

  
- **Repositories:**\
    I deliberately introduced the repository pattern because:

    -   It provided clear separation between data persistence and business logic.
    -   It made unit testing easier by isolating data access.
    -   It enhanced maintainability, making future modifications or database migrations easier to manage.


  - **Dedicated API Routes File:**\
    I created a specific `api.php` routes file separate from Laravel's default `web.php` file. I found this significantly improved clarity, ensuring the API endpoints are logically organized and easy to locate.

## Testing Approach

-   **Unit Tests:**\
    I focused primarily on the service and repository layers, carefully mocking Laravel's Eloquent queries to isolate and rigorously test complex business logic and database interactions.

  
- **Feature Tests:**\
    I wrote integration tests that simulate realistic HTTP interactions, ensuring endpoint correctness, validation accuracy, and proper response structures.

  
- **Choice of Local Database:**\
    For tests, I opted for an SQLite in-memory database to achieve rapid execution. However, I recognized that in a production environment, using PostgreSQL (with PostGIS) or MySQL (with GIS extensions) would significantly enhance the capability and performance of geographic queries.

## Reflections & Rationale on My Approach

-   **Simplicity:**\
    I consciously avoided unnecessary complexity by relying solely on Laravel's built-in capabilities. My aim was to keep the solution clean, readable, and straightforward, making it easy for future maintainers to understand and build upon.


- **Maintainability:**\
    By clearly structuring the app into layers---DTOs, form requests, repositories, services, and controllers---I made it easier to test, modify, and extend each component independently, significantly enhancing long-term maintainability.

  
- **Correctness:**\
    Throughout development, I consistently validated data rigorously and implemented careful error handling. My use of geographic calculations (bounding-box and Haversine formula) was thoroughly tested and verified through unit and feature tests, giving me confidence in the application's accuracy.

  
- **Technology Choices:**\
    I deliberately chose not to introduce any third-party packages beyond what Laravel provides out-of-the-box. This decision simplified dependency management and reduced future maintenance overhead.

  
- **Performance:**\
    For data imports, I utilized chunking to process large CSV datasets efficiently. Geographic queries were optimized using bounding boxes to limit the number of records evaluated before performing detailed calculations. For future iterations, I acknowledged that using specialized GIS database extensions would offer additional performance improvements.

  
- **Documentation:**\
    I provided clear and informative comments, named methods and variables intuitively, and structured PHPDoc annotations thoughtfully, enhancing readability and maintainability for myself and future developers.

  
- **Security:**\
    I ensured all API endpoints required authentication through Laravel Sanctum, implemented robust validation to protect against malicious inputs, and avoided leaking sensitive error information to end-users, logging detailed information internally instead.

## Considerations for Future Improvements (Constrained by Time)

Given additional time, there are several areas I would revisit or enhance further:

-   **Postcode Validation:**\
    Implement robust postcode validation rules, possibly using dedicated Laravel validation packages or external services designed specifically for validating UK postcodes.

  
- **Latitude/Longitude Validation:**\
    Ensure precise validation of latitude and longitude data to prevent invalid or out-of-range values from entering the database.

  
- **Historical Postcode Handling:**\
    Decide whether to retain historical postcode information during imports, and determine how best to version or manage changes to postcode data over time.

  
- **Enhanced Console Experience:**\
    Consider third-party console enhancement packages to make command-line interactions visually clearer, particularly beneficial when importing or processing large datasets.

  
- **CSV Handling with League/CSV:**\
    Although my current built-in approach worked well, I'd consider evaluating dedicated CSV parsing libraries (like `league/csv`) for potentially increased robustness or performance benefits in a production scenario.

  
- **GIS Database Support:**\
    Implement and evaluate PostgreSQL with PostGIS or MySQL GIS extensions in production environments for better geographic query performance and functionality.

  
- **Comprehensive API Documentation:**\
    Introduce detailed API documentation (such as Swagger/OpenAPI) to improve integration experiences and clearly communicate API capabilities.
