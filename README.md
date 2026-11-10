<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Family tree views

The family tree has a dedicated illustrated view at `/family-tree/tree` (or `/view/tree` for family PIN viewers). Open **View living tree** from the existing diagram. Ancestors sit at the base; each recorded parent group has a branch leading to its children, with spouses placed side by side. Scroll through the tree, use the zoom controls from 50% to 200%, search by name, or select a portrait to inspect family ties.

## Clan event management

- Browse the public calendar at `/events`, with search, date/status filters, and event details.
- Sign in and open **Manage events** to create events, set dates and times, upload posters, and save drafts or publish events. Drafts are visible only to their organizer and admins; cancelled events remain visible with a cancellation notice.
- Organizers manage their own events. Admins manage all events. Event links stay unchanged when titles are edited.
- Signed-in members can RSVP **Going**, **Maybe**, or **Not going** and change their response while RSVPs are open. Individual attendance is visible only to the organizer and admins.
- Times use the selected event time zone (Asia/Manila by default). Blank times mean all day; an end time must fall later on the same day. RSVPs close at the end time, or start time if no end is set, or midnight for an all-day event. Calendar date filters use the Manila calendar date.
- Existing events remain published after migration. Run `php artisan migrate` and `npm run build` when deploying these changes. Poster uploads require the public storage link (`php artisan storage:link`).

Validation: `php artisan test` covers event management, visibility, scheduling, attendance, and storage cleanup.

## Family newsletters

- The public archive at `/newsletters` shows published issues, with search by title or summary.
- Signed-in authors can create and edit issues at `/newsletters/manage`; admins can manage every issue. Each issue has a title, issue date, optional summary and cover image, and plain-text content whose line breaks are preserved.
- Drafts and archived issues are visible only to their author and admins. Changing an issue to **Published** makes it public immediately. The issue date labels the issue; it does not schedule publication.
- Cover images are stored on Laravel's private `local` disk and served through an access-checked route. Replacing or deleting an image cleans up the old file. Editing the title keeps the issue URL stable.
- Run `php artisan migrate` and `npm run build` when deploying. This module publishes newsletters on the site; it does not email them to subscribers.

Validation: `php artisan test` covers publishing, privacy, author/admin permissions, search, pagination, validation, and cover access.

## Video messages to next generations

Signed-in members can attach an MP4, WebM, MOV, or M4V video (up to 10 MB) when adding or editing a message. A message can contain text, video, or both. Videos stay in private storage and play for signed-in members and family viewers with a valid access PIN. Replacing or removing a video deletes the old file. Run `php artisan migrate` to add video support to existing messages.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
