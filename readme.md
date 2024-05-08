# "Top 10" And Other SMS (Scan This QR Code) Website

The original concept was to have people text "math" to our SMS number, and the response would link them to a page on this sms mini-site.  Or for customers to scan physical **QR code signs** in each of the categories throughout the stores.  The signs could say things like:

- Top 10 items in Math
- Our Favorite Picks for Teaching Multiplication
- See a gallery of Farm Fun Decor

The results shown would be either a hand-curated or a dynamic database-driven page of products.


## Hand-Curated Pages

`farm.php` is an example of a hand-curated page for the "Farm Fun" decor theme.

https://teachingstuff-sms.azurewebsites.net/farm.php


## Dynamic From Database

`index.php` uses input from the query string to dynamically list products for a category.  See the code for more.

https://teachingstuff-sms.azurewebsites.net/?category=MATH

> The code is using old database tables and fields, and needs significant updates before it can be used.


---

## State of This Project

Aside from some proof-of-concept work, the project never materialized, and was never launched.

Some "View the Gallery" QR signs have been created, but they've pointed to a static WordPress blog post at blog.teachingstuff.com


### On Azure

A **Web App Service** `teachingstuff-sms` and an associated dev slot were created and used for the proof-of-concept work, but have since been removed from Azure.

The related `teachingstuff-sms` **Application Insights** resource has also been removed from Azure.

> Azure App Name: **`teachingstuff-sms`**


### On GitHub

View the GitHub repository:

https://github.com/teachingandlearningstuff/sms


### On Cloudflare *(Custom Domains & Subdomains)*

There is no custom domain or subdomain for this project.
