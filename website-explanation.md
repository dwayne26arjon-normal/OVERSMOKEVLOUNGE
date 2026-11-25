# Oversmoke Vlounge Website: Project Explanation

## 1. Overview

Oversmoke Vlounge is an online store with a customer-facing storefront and a separate admin dashboard. Customers can browse products, place cash-on-delivery orders, track order status, chat with support, and submit eligible refund requests. Store staff use the dashboard to manage products, inventory, orders, customer support, and refund reviews.

## 2. Technology

- **PHP 8.2** and **Laravel 12** provide the server-side application.
- **Blade** renders the pages. The frontend also uses HTML, CSS, and JavaScript.
- **Vite** builds frontend assets. Tailwind CSS is configured in the frontend build.
- **Laravel Eloquent** is used to read and write relational database records.
- The database connection is configured through environment settings. The project supports MySQL; automated tests use an in-memory SQLite database.
- **PHPUnit** runs the Laravel feature and unit tests.

The application is organized around Laravel routes, controllers, models, and views:

1. A browser requests a page or submits an action.
2. Laravel routes the request to the relevant controller or handler.
3. The application validates the request and applies authentication and authorization checks.
4. Eloquent reads or updates database records.
5. Laravel returns a rendered page or a JSON response for JavaScript to process.

Customer and admin routes are organized in `routes/user.php` and `routes/admin.php`. The main route file loads both.

## 3. Customer features

- **Product browsing:** Customers can browse active products, see featured products, view product details, and choose available product options.
- **Homepage carousel:** An active product marked for the homepage carousel appears as a slide. There is no fixed slide limit. The carousel advances automatically, and its frame adapts to image dimensions to display the full artwork without cropping.
- **Cart:** Cart contents are kept in the browser's `localStorage`, so they can remain available while the customer navigates the site.
- **Checkout and orders:** Checkout submits the order to Laravel, which stores the order and its items. The current payment method is cash on delivery; the site does not process online card or e-wallet payments.
- **Account and delivery details:** Customers can register, sign in, manage account details, and provide delivery information.
- **Order updates:** Customers can view their orders and receive status updates while the orders are being handled.
- **Refund requests:** Eligible customers can submit a damage-related request with a description and supporting evidence. The store reviews the request and records the decision. Any actual refund payment is arranged manually outside the website.
- **Support chat:** Customers can exchange messages with store staff through the support chat.

## 4. Admin and staff features

Authorized admin and staff accounts can use the dashboard to:

- Add and edit products, upload product and carousel artwork, and mark products active or featured.
- Manage inventory and product options.
- Review orders and update their status.
- Review refund requests, evidence, and customer-provided payment details, then record a decision or completion.
- Respond to customer support conversations.
- Review notifications about actions needing attention.

Staff management is restricted to owner-authorized routes. The application stores products, orders, order items, users, refund requests, evidence, and chat data as related database records.

## 5. Notifications and polling

Some screens periodically ask the Laravel application whether new data is available. This technique is called **polling**:

1. JavaScript sends a request to an internal Laravel endpoint.
2. Laravel checks the relevant database records and returns data, often as JSON.
3. JavaScript updates the page if something changed.
4. The browser repeats the check after a short interval.

For example, order updates and support-chat indicators are checked about every five seconds, while notification lists are checked about every ten seconds. Some checks pause when the browser tab is hidden; customer chat message checks run while the chat is open.

This is **near-real-time polling**, not WebSockets or instant server push. It can take until the next check for an update to appear. The homepage carousel's automatic slide changes are a separate browser-side behavior, not a server notification.

## 6. APIs and external services

The frontend uses **internal HTTP/JSON endpoints** provided by this Laravel application. JavaScript can call these endpoints with `fetch`, for example to retrieve updates or submit a chat message. These endpoints are APIs, but they are not third-party APIs.

The current checkout uses cash on delivery, and refund payouts are arranged outside the website. Therefore, the website does not use an online payment gateway for these workflows.

## 7. Security and data handling

- Laravel authentication and role checks protect customer and admin features.
- Form data is validated by the application before it is saved.
- Passwords are stored as hashes, not readable passwords.
- Refund evidence is accessed through authorized routes rather than treated as public content.
- Database credentials and other environment-specific settings belong in environment configuration, not source code.

## 8. Testing

The project uses Laravel's test runner and PHPUnit. Feature tests make HTTP requests to the application and verify responses and behavior—for example, that active featured products appear in the homepage carousel and inactive products do not.

Run the test suite with:

```text
php artisan test
```

## 9. Short presentation script

> “Our website is an online store built with PHP and Laravel. Laravel handles the routes, business logic, authentication, and database operations, while Blade, CSS, and JavaScript create the user interface. Customers can browse products, keep items in a cart, place cash-on-delivery orders, track order status, chat with support, and submit refund requests. Staff manage products, inventory, orders, support conversations, and refund decisions through the admin dashboard. Some updates use polling: the browser periodically checks the website's own Laravel endpoints, so those updates are near-real-time rather than WebSocket push. The site uses internal APIs, and it does not process online payments.”

## 10. Useful terms

- **Route:** Maps a URL and HTTP method to application behavior.
- **Controller:** Handles a request, applies application logic, and returns a response.
- **Model:** Represents a database record and its relationships.
- **View / Blade:** Template used to render the HTML page.
- **API endpoint:** A URL that accepts or returns structured data, often JSON.
- **Polling:** Repeatedly checking an endpoint for updates.
- **`localStorage`:** Browser storage used here to retain cart data between page navigations.
- **Eloquent:** Laravel's ORM, which provides a model-based way to work with database records.
