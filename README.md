# Castor & Otters, Inc., a Lynx starter

**[See the live demo at meeting-rooms.lynxcms.org](https://meeting-rooms.lynxcms.org/)**, in English and [French](https://meeting-rooms.lynxcms.org/fr). The [room screens](https://meeting-rooms.lynxcms.org/demo/screens) show its lobby TVs and door tablets.

### Try the admin

Open the [demo admin](https://meeting-rooms.lynxcms.org/lynx) and choose **Admin**, **Editor** or **Staff** on the sign-in page. No password is needed.

Anyone can sign in, so the demo goes back to its original content at the start of every hour. Your edits and uploads are deleted then.

---

The website of a law firm specialized in wildlife, in Vancouver and Montreal, with its meeting rooms on screens: a lobby TV per office and a tablet at each door. Staff book rooms on the site, in the admin, or by asking their AI assistant. Built on [Lynx CMS](https://lynxcms.org/cms/docs/) with [Laravel Zap](https://www.laravel-zap.com/) for calendars.

It is the code of the guide [Build a meeting rooms app](https://lynxcms.org/cms/docs/guides/meeting-rooms/), which explains how Lynx serves as the base of an application: rooms are pages, bookings are actions, and the admin, the screens, the staff's pages and AI assistants all go through them. When Lynx releases a new version, this repository is updated to it and its tests run.

## Run it

You need PHP 8.5, Composer and Node.

```bash
git clone https://github.com/LynxCMS/starter-meeting-rooms.git castor-otters
cd castor-otters
composer setup
php artisan db:seed
composer dev
```

`composer setup` installs the dependencies, creates `.env` and the SQLite database, runs the migrations and builds the CSS and JavaScript. `php artisan db:seed` adds the firm, its offices, rooms, screens and a few meetings. `composer dev` serves the site at http://localhost:8000 and sends queued mail to the log. With Laravel Herd, set `APP_URL` to the site's `.test` address instead.

| Who | Email | Password | Sees |
| --- | --- | --- | --- |
| The owner (superuser) | `owner@meeting-rooms.example` | `change-me-now` (`ROOMS_OWNER_PASSWORD` before seeding) | everything, at `/lynx` |
| Mia, a manager | `mia@meeting-rooms.example` | `password` | the admin's Bookings, every meeting |
| Ana, staff | `ana@meeting-rooms.example` | `password` | `/book` and `/my-bookings` |

The site is in English, and in French under `/fr`. The Rooms section asks for the password `four otters and a beaver` (`ROOMS_PASSWORD` before seeding). Open a screen from its page in the admin (Screens, then the Kiosk link tab).

Start over with `php artisan migrate:fresh --seed`.

## What is in it

### Rooms and bookings

- Rooms are Lynx pages under **Rooms**, with their office, seats, floor, equipment, a color and opening hours: The Lodge and Kingfisher in Vancouver, Saint-Laurent and Huard in Montreal.
- Each room page has a row in `rooms` (`App\Models\Room`), which Zap hangs schedules on. Saving a room page rewrites its availability from its opening hours; moving it to the trash removes it with its schedules, and restoring brings it back.
- A room's page shows its opening hours, and its meetings and free times in the next days it opens.
- Screens (a lobby per office, a door per room) and announcements are pages too.

Bookings are Lynx actions, so the admin, the booking page and AI assistants share their rules:

| Action | Who | Does |
| --- | --- | --- |
| `rooms.book` | staff | Books a room for a period on one day, private or not. |
| `rooms.change` | the organizer, or a manager | Moves a booking to another time, day or room, or renames it. |
| `rooms.cancel` | the organizer, or a manager | Cancels a booking, or lifts a blocked time. |
| `rooms.block` | managers | Blocks a room for maintenance or an event. |

Rules (`config/rooms.php`): inside the room's opening hours, 15 minutes to 4 hours, not in the past, and 5 minutes clear of other meetings and blocked times. A refusal names the times that are taken, never someone else's title. The seeder adds `ana@meeting-rooms.example` (staff) and `mia@meeting-rooms.example` (manager), password `password`, and a few meetings on the next working day; each room's page lists them, private ones as "Busy".

Try the rules in `php artisan tinker`:

```php
use App\Actions\Bookings\BookRoom;
use LynxCms\Contracts\Actions\Actor;

$ana = Actor::user(App\Models\User::where('email', 'ana@meeting-rooms.example')->value('id'));
$book = fn (array $input) => lynx()->actingAs($ana, fn () => lynx()->run(BookRoom::class, $input + ['room' => '/rooms/lodge/', 'title' => 'Try']))->value;

$book(['date' => now()->next('Monday')->toDateString(), 'from' => '12:00', 'to' => '13:00']);  // booked
$book(['date' => now()->next('Monday')->toDateString(), 'from' => '12:30', 'to' => '13:30']);  // "The Lodge is taken from 12:00 to 13:00. ..."
$book(['date' => now()->next('Saturday')->toDateString(), 'from' => '10:00', 'to' => '11:00']); // "The Lodge is closed on Saturdays."
```

### The firm

- English and Canadian French: every section has a French address (`/fr/domaines`, `/fr/equipe`, `/fr/nouvelles`, `/fr/bureaux`, `/fr/contact`, `/fr/salles`), and the page password form speaks French there too.
- Two offices, each in its time zone: Vancouver (Pacific) and Montreal (Eastern). Each room belongs to an office: its bookings are wall-clock times in that zone, "already passed" is judged there, and its page's "today" is the office's.
- The Rooms section (`/rooms`) is behind one page password, `four otters and a beaver` (`ROOMS_PASSWORD` before seeding), which covers every room below it and is remembered for 30 days. Office managers who are signed in skip it.

### The site's look and content

- A lakeside palette (birch paper, lake ink, reeds, beaver copper) with Fraunces and Instrument Sans, bundled from Fontsource by Vite, so pages request nothing elsewhere.
- Thirteen illustrations, one per animal or scene, in `public/images/illustrations`: SVGs generated for the site with Magnific (text to SVG) in one muted poster style and the site's palette, then optimised with SVGO (keeping their `viewBox`). They are served as image files, so browsers cache them across pages. A lawyer or practice area picks one in its **Illustration** field; a lawyer's uploaded **Photo** wins over it.
- The rooms' password form (`resources/views/vendor/lynx-page-password`) and the 404 page use the site's frame.
- No straight lines by the water: links are underlined with waves that ripple on hover (`link-wave`, and links in rich text), rows are divided by wavy rules (`wave-rule`), and coloured sections meet the page with wavy edges (`<x-wave>`).
- The home page opens with a catch phrase (**Catch phrase** field) and three figures with a dry line each (**Figures**, a repeater), in both languages.
- Content in English and French, written by hand in `database/seeders/content/*.php` and seeded by `SiteContentSeeder`: five practice areas, ten lawyers and paralegals, six cases and news items, both offices, contact, and the legal and privacy pages.
- A practice area names its lawyers; a lawyer's page finds its areas from them, and an office's page lists who works there.
- The templates' own words are in `lang/{en,fr}/site.php`. The rooms' pages take the same look and speak French too.

### Lynx's modules on the site

Set up by `SiteFeaturesSeeder`:

- **Menus** (`lynxcms/menus`): **main** (the five sections) in the header and **footer** (legal notice, privacy), edited in the admin's Menus screen and drawn with the site's own views (`resources/views/menus`). The staff's Rooms link stays in code, since menus leave out pages behind a password.
- **Consultation request** (`lynxcms/forms`): `App\Forms\ConsultationForm`, two steps in English and French, on the contact page. Submissions land in the admin's Forms inbox and are mailed to users who may read them. French validation messages are in `lang/fr/validation.php`.
- **SEO** (`lynxcms/seo`): titles in the pattern `{title} · {site}`, descriptions from summaries, Open Graph, hreflang for both languages, and JSON-LD: lawyers as `Person`, offices as `ProfessionalService` with their address, posts as `NewsArticle`. Settings are under SEO in the admin. `/robots.txt` comes from Lynx (Laravel's static file is gone); outside production it blocks crawlers.
- **Sitemap** (`lynxcms/sitemap`): `/sitemap.xml` lists every public page in both languages, without the rooms or the search page.
- **Search**: `/search` (`/fr/recherche`) reads Lynx's search index: titles, summaries, texts, and the lawyers' species and fun facts. The magnifying glass in the header leads there.
- **Page cache** (`lynxcms/cache`): stores pages guests see as HTML, contact form included (its CSRF token is refreshed), but never search results or the rooms. Off locally (`LYNX_PAGE_CACHE=false`) while the design changes; turn it on in production.

### The page not found and the admin's look

- **The page not found** is a page in the tree, `/not-found/` (`/fr/page-introuvable`), which Lynx shows with status 404 for any address that matches no page. Editors change its title, text and drawing like any page; the button is a site text. Its view is `templates/not-found.blade.php`; `errors/404.blade.php` stays for when no page is chosen. Pick another page under **Settings > General** in the admin.
- **The admin's look**: the sign-in screens and the sidebar show the firm's wordmark (`public/images/logo.svg`) on birch, with ink buttons. Superusers change them under **Settings > General**; the seeder sets them through `admin.branding`.

### Bookings and screens in the admin

For people with `rooms.manage` (managers and the owner):

- **Bookings** in the admin's sidebar: an office's rooms against the working week, in the office's time zone. Click a day to book or block a room, and a booking to change or cancel it. Every save runs the booking actions (`rooms.book`, `rooms.change`, `rooms.cancel`, `rooms.block`), so the office's rules and Zap's conflict check apply, and refusals show on the form.
- A room's page editor has a **Bookings** tab with the same, for that room.
- A screen's page editor has a **Kiosk link** tab: **Issue link** shows the screen's secret address and a QR code once (`screens.issue_link`); issuing again replaces it, and **Revoke** cuts the screen off (`screens.revoke_link`). Only a hash of the token is kept, in the `kiosk_links` table, never in a field.
- `/screens/{token}` is the kiosk link. It runs without a session (no cookie) and answers `no-store` and `noindex`.
- The admin ships its CSS built, so the app's admin views get their own utilities (`resources/css/admin.css`, in the admin's colour tokens), loaded through `lynx-admin.stylesheets`.

### The screens

Open a screen's kiosk link on its TV or tablet (the Kiosk link tab in the admin):

- **Lobby**: the office's rooms (or the ones picked in the screen's **Rooms** field), each free, busy or unavailable now, with the meeting's progress and what is next; announcements running now rotate in a band. When every room is closed, the firm's logo (Settings > Logo, else the wordmark and the beaver).
- **Door**: the room's big state in its colour (reeds green when free, copper when busy), the day's meetings, and two buttons: **Check in** (from `rooms.check_in_early_minutes` before the start) and **End meeting now**. Closed rooms show the logo.
- Private meetings read "Busy", with no organizer. Each screen speaks its **Language on the screen** (Montreal's are French).
- The page polls `/screens/{token}/state` every `rooms.screen_poll_seconds` (20); the clock and progress bars move every second between polls from the server's minute. A failed poll keeps the last state and shows "Reconnecting…". Screens ask the browser to stay awake (Wake Lock) and reload themselves at `rooms.screen_reload_hour` (3:00).
- The buttons post to the kiosk link and run `rooms.check_in` and `rooms.end_early` as the system, on the screen's own room only. Screens run no Livewire: kiosk links have no session, so the page is server-rendered with Alpine (`resources/js/kiosk.js`).
- **No-shows**: `php artisan rooms:release-no-shows` (scheduled every minute: run `php artisan schedule:run` from cron) frees the rest of meetings nobody checked into within `rooms.check_in_minutes` (10), in rooms that need a check in (`rooms.release_no_shows`, in the activity log), and mails the meeting's organizer.

### Booking for staff

Signed-in staff (anyone with `rooms.book`, such as `ana@meeting-rooms.example` / `password`):

- **Book a room** at `/book` (`/fr/reserver`): pick an office, a day, how long, how many seats and what the room needs; each room that fits lists its free start times every quarter hour, with the gap between meetings already kept (`App\Rooms\Slots`). Pick one, name the meeting (private if needed), and it is booked through `rooms.book`.
- **My bookings** at `/my-bookings` (`/fr/mes-reservations`): their meetings that have not ended, by day, to move, rename (`rooms.change`) or cancel (`rooms.cancel`).
- Both are Lynx pages (templates `book` and `my-bookings`), hidden, so they never appear in menus, search or the sitemap, and `noindex`. Editors change their title and summary in both languages. A gate in `RoomsServiceProvider` sends people who are not signed in to sign in and back, and refuses people who may not book. Signed-in staff see both pages and **Sign out** in the footer instead of **Staff**; signing out returns to the home page.
- **Refusals are texts of the booking page.** When the office's rules refuse a booking, the actions throw `App\Bookings\BookingRefused` with a reason and its values (the room, the times, the minutes). The booking page and My bookings show it as one of the booking page's Texts (`refusal.*`, in `livewire/partials/refusal.blade.php`), which editors reword in English and French; values go in through `@string`'s `replace:`. The admin, MCP clients and agents keep the actions' English messages.

### Repeating meetings

- **Booking a series**: on the booking page or in the admin, a meeting can repeat every week, every two weeks, or every month on the same weekday (the second Monday, the last Friday), until a date within a year (`rooms.book` with `repeat` and `until`). Zap checks every date of the series for conflicts.
- **One date or the whole series**: changing or cancelling a meeting of a series asks which (`occurrence` and `scope` on `rooms.change` and `rooms.cancel`). One date is split out of the series (`App\Bookings\Series`): the series ends the day before, a copy carries on the day after, and a changed date becomes a meeting of its own, all with the same series id. The whole series takes a new time, title, privacy or invitations (it keeps its days and room). A new time applies to every date, including those changed alone; a change that leaves the time as it was keeps each date's own.
- **Screens and no-shows**: checking in, ending early or releasing a no-show splits that day's meeting out first, so the rest of the series stays as it was.
- **Reading a day**: Zap's `forDate()` matches every Monday of a "second Monday" pattern and leaves the ordinal to the pattern's own `shouldCreateRecurringInstance()`; `Series::matches()` applies it wherever the app lists a day.
- **My bookings** lists each date of a series over the next six weeks, marked ↻, with **Cancel this one** and **Cancel the series**.

### Mail, invites, reminders and feeds

- **Invitations**: the organizer invites colleagues when booking (the **Invite a colleague** picker on the booking page, My bookings and the admin's form; `attendees`, as emails, on `rooms.book` and `rooms.change`). Only the organizer says who is invited, and for a series, only for the whole series. A manager who opens someone else's meeting sees who is invited but cannot change it.
- **Mail**: booking, changing and cancelling send each person concerned a queued mail with an `.ics` (`App\Mail\MeetingMail`): the organizer "Booked", the people invited "Invitation", then "Updated" or "Cancelled"; someone taken off the list gets a cancellation. Mail speaks the language the meeting was booked in (book on `/fr/reserver` and it is French). The actions record a `MeetingChanged` event, so nothing is sent for a dry run or a refused change. Seeded meetings mail no one.
- **The `.ics`** (`App\Calendar\Ics`, built with spatie/icalendar-generator): one stable `UID` per meeting (`series-{uuid}` or `booking-{id}`), `METHOD:REQUEST` or `CANCEL`, and a `SEQUENCE` that grows with time, so Outlook, Google Calendar and Apple Calendar update the event in place. Every mail carries the whole meeting as it now stands: a series is one event with its `RRULE`, the dates taken out (`EXDATE`) and the dates changed alone (`RECURRENCE-ID`), read whole by `App\Calendar\Meeting`. The rooms' mailbox (`MAIL_FROM_ADDRESS`) is the `ORGANIZER`, so its updates are accepted; replies to it are not read.
- **Reminders**: `php artisan rooms:send-reminders` (scheduled every minute) mails the organizer and the people invited `rooms.reminder_minutes` (15, `0` turns them off) before each meeting, once per date, and not for meetings booked after their reminder was due. A private meeting's reminder leaves its title out of the subject.
- **Calendar feeds**: `/calendar/rooms/{token}.ics` for a room (the room's **Calendar feed** tab in the admin, managers) and `/calendar/me/{token}.ics` for a person (**Your calendar** on My bookings): one event per date from a month back to half a year ahead. Room feeds show private meetings as "Busy" and blocked times; a person's feed shows their meetings, as organizer or invited, in full. Addresses are secret tokens kept only as hashes, like kiosk links (`rooms.issue_feed`, `rooms.revoke_feed`), and are read without a session.
- **Locally**: mail goes to the log (`MAIL_MAILER=log`) through the database queue, so run `php artisan queue:work` (or `composer dev`) to send it. The demo seed adds Oscar, Pascale and Théo as staff to invite.

### AI assistants over MCP

- **Connecting**: each person gets a token for their assistant under **Your AI assistant** on My bookings, shown once with a `claude mcp add` command and the JSON other MCP clients take. The assistant reaches Lynx's MCP server at `/mcp/lynx` (`LYNX_MCP_WEB=true`, behind Sanctum) and acts as that person, with their roles. One token per person: a new one replaces the old, and **Stop the token** revokes it (`rooms.issue_assistant_token`, `rooms.revoke_assistant_token`). Those two actions are not MCP tools, so an assistant never mints or sees a key. The token carries only the `lynx:mcp` ability.
- **Reading**: three read tools (`App\Agents`), each needing `rooms.book`. `find_free_rooms` takes a date, a length, seats, equipment, an office and an optional start time, and answers the rooms that suit with the windows a meeting can start in (the booking page's own logic, `App\Rooms\FreeRooms`). `room_schedule` answers a room's day: opening hours, meetings with their booking ids, and free times. `my_bookings` lists the meetings the person organizes, with the people invited. Private meetings read "Busy", with no organizer, unless the person asking organizes them, is invited or manages rooms.
- **Writing**: the booking actions are MCP tools already (`rooms_book`, `rooms_change`, `rooms_cancel`, `rooms_block`…), with a `dry_run` flag, the same rules and permissions as the pages, mail to the people concerned and the activity log. "A room for six with video tomorrow at 10" is `find_free_rooms`, then `rooms_book` as a dry run, then for real.
- The built-in agent (`lynxcms/ai`) is not installed: it needs an AI provider key, and the read tools would serve it unchanged.

## Hosting

One Laravel app serves the website, the staff's pages, the admin, the screens, the calendar feeds and the MCP server. It runs wherever Laravel runs, and SQLite is enough for an office of this size (about 10 rooms, a few screens, under 100 staff).

### The server

- PHP 8.5 with the extensions Laravel needs plus `intl` and `pdo_sqlite`, a web server whose root is `public/`, and HTTPS. Screens ask the browser to stay awake and assistants send their tokens, which both need HTTPS. Node is only needed to build the CSS and JavaScript.
- The server's clock must be right (a time server, such as `systemd-timesyncd` or `chrony`): screens, check in, no-shows and reminders all read it.
- The web server's user writes `storage/`, `bootstrap/cache/` and the `database/` folder (SQLite writes a journal next to the file).

### The environment

| Setting | In production |
| --- | --- |
| `APP_ENV`, `APP_DEBUG` | `production`, `false` |
| `APP_URL` | The firm's address, with `https://`. Kiosk links, feed addresses, invites and the assistants' setup use it. |
| `APP_TIMEZONE` | Leave it at `UTC`. Each room takes its time zone from its office's page. |
| `MAIL_MAILER`, `MAIL_FROM_ADDRESS` | A real mailer. The from address is the rooms' mailbox and the organizer of every invite, so calendar apps accept its updates; replies to it are not read. |
| `LYNX_PAGE_CACHE` | `true`. Guests get stored pages; the rooms, kiosk links, feeds, staff pages and the MCP server are never stored. |
| `LYNX_BACKUPS_SCHEDULE` | `daily`, for Lynx's content backups (`lynx:backup`, 14 kept). |
| `LYNX_MCP_WEB` | `true` lets staff connect AI assistants; `false` turns the MCP server off. |
| `ROOMS_*` | The booking rules, reminders and screens (`config/rooms.php`). |

### Processes

The scheduler runs every minute from cron. It releases no-shows, sends reminders, publishes scheduled pages and copies the database every night:

```cron
* * * * * cd /var/www/castor-otters && php artisan schedule:run >> /dev/null 2>&1
```

A queue worker sends the meeting mail and the consultation requests. Keep it running with Supervisor or systemd:

```bash
php artisan queue:work --tries=3
```

### Deploying

The first time, set `ROOMS_OWNER_PASSWORD` and seed. The seed is a demo: it also creates staff accounts with the password `password` and a few meetings. Delete those accounts under Users in the admin and invite the real staff (Lynx sends invitations; there is no registration).

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
```

Run the same steps for each new version. Templates and fields are made by the seeders; once the site is live, change them in the admin, or export them with `php artisan lynx:schema:export` in development and run `php artisan lynx:schema:apply --dry-run`, then `lynx:schema:apply`, on the server.

### Docker

The repository has a `Dockerfile`: one image with PHP, the web server on port 80, the scheduler and the queue worker, so cron and Supervisor aren't needed. On its first start it migrates and seeds the demo firm.

```bash
docker build -t castor-otters .
docker run -p 8000:80 -v castor-otters:/app/storage \
  -e APP_KEY="$(php artisan key:generate --show)" \
  -e APP_URL=http://localhost:8000 \
  -e ROOMS_OWNER_PASSWORD=choose-one \
  -e SITE_DEMO_RESET=true \
  castor-otters
```

| Setting | What it does |
| --- | --- |
| `APP_KEY` | Required. A key from `php artisan key:generate --show`. Keep it: changing it signs everyone out and breaks feed addresses. |
| `APP_URL` | The site's address. Kiosk links and images are built from it. |
| `ROOMS_OWNER_PASSWORD` | The owner's password for the first seed. The seed's staff accounts use the password `password`, so a real firm deletes them. |
| `SITE_DEMO_RESET` | `true` only for a public demo where anyone may sign in. The sign-in page gets one button each for Admin, Editor (Mia, a manager) and Staff (Ana), with no password. `/demo/screens` lists every screen with a link anyone may open, and the rooms stay open round the clock with meetings around each reset, so the screens show something at any hour. Every hour the site goes back to the seeded firm, deleting edits, bookings and uploads, and the admin's sidebar says so. |
| `MAIL_MAILER` | `log` in the image, so a demo never mails anyone. Set a real mailer for a real firm. |

On Dokploy, Coolify or similar: build from the `Dockerfile`, set the variables above, mount a volume at `/app/storage`, and give the domain HTTPS with container port `80`. A change of variables takes effect after a rebuild or redeploy.

### Backups

Two kinds, both kept on the server, so copy them elsewhere too:

- `php artisan rooms:backup-database` (every night at 02:30) copies the SQLite database into `storage/app/private/backups`, keeping 14 (`ROOMS_DATABASE_BACKUPS_KEEP`). It holds everything: pages, bookings, accounts, kiosk links and tokens. To restore, stop the queue worker and put a copy in place of `database/database.sqlite`.
- `php artisan lynx:backup` writes the schema and the pages as a content package, which `lynx:import` brings into another Lynx site. It leaves out bookings and accounts.

### Screens

Open each screen's kiosk link on its TV or tablet in a full screen browser, for example `chromium --kiosk --noerrdialogs --disable-session-crashed-bubble https://firm.example/screens/…`, and turn off the device's own sleep. A kiosk link is the screen's only key: issue a new one if a device is lost, which cuts off the old. Screens reload themselves every night at 3:00 to pick up new code.

### Assistants

Staff connect their assistant with the token from My bookings, over HTTPS only. Each token acts as its owner, with their roles, and is revoked from the same page. Managers revoke a person's access by removing their roles or their account.

## Checks

After changing fields, run `php artisan lynx:phpstan` and `vendor/bin/phpstan clear-result-cache` so PHPStan sees them.

```bash
php vendor/bin/pest
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

## License

MIT. The illustrations in `public/images` are the firm's own and come with the code under the same license.
