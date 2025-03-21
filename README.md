## WhatsApp Clone

<p>A real-time chat application inspired by WhatsApp, built using Vue.js, Inertia.js, Laravel, and PrimeVue.</p>

---

### Installation
Follow these steps to get the project up and running on your local machine:

1. Clone the repository:

```
$ git clone https://github.com/BlackyDrum/whatsapp-clone.git
```

2. Navigate to the project directory:

```
$ cd whatsapp-clone
```

3. Install the dependencies:

```
$ composer install
```

4. Create a copy of the `.env.example` file and rename it to `.env`. Update the necessary configuration values, such as database credentials (use `PostgreSQL` or `MySQL`).

5. Generate an application key:

```
$ php artisan key:generate
```

6. Run the database migrations:

```
$ php artisan migrate
```

7. Install JavaScript dependencies:

```
$ npm install
```

8. Run the `composer dev` command:

```
$ composer dev
```

9. Visit `http://localhost:8000` in your web browser to access the application.

### Real-time Setup

This project uses Laravel Reverb for real-time messaging. Ensure you set up your `.env` file with:
```env
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
```
After that, make sure to start the Websocket Server:

```
$ php artisan reverb:start
```

### License
This project is licensed under the MIT License.
