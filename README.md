# Hero Movies Backend

## Description

A simple WordPress plugin that offers a RESTful API to provide a list of movies managed within WordPress.

### Requirements

- PHP 8.2 or Latest.
- Composer
- Installed WordPress Application
- Postman or any alternative API client

### Step by Step Guide
- You need to execute "composer install" within the plugin directory in the terminal.
- Access your WordPress Dashboard.
- Navigate to Plugins, then select Add New Plugin.
- Click on Upload Plugin, and proceed to upload the project zip file.
- After installing the plugin, locate the "Movies" option in the sidebar menu.
- Click on "Import Movies" and select the provided movies.csv file included in the project.
- In Postman, you'll find the necessary endpoints to utilize. Initially, log in to obtain a token, then set it as the Authorization Bearer. Please refer to the provided code example for guidance.

```
curl -X POST http://your-wordpress-site.com/wp-json/herothemes/v1/login \
-H "Content-Type: application/json" \
-d '{
  "username": "your_username",
  "password": "your_password"
}'

```

```
curl -X GET http://your-wordpress-site.com/wp-json/herothemes/v1/movies?paged=1&posts_per_page=10 \
-H "Authorization: Bearer <your_token>"
```

```
curl -X GET http://your-wordpress-site.com/wp-json/herothemes/v1/movies/<post_id> \
-H "Authorization: Bearer <your_token>"
```

## Help

Should you have any inquiries, feel free to email me, and I'll be happy to provide further clarification johanss.zip@gmail.com .
