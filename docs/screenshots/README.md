# Screenshots of the running system

Every screen of the application, grouped by the role that reaches it. These are captures of
the application running against the project **MySQL** database with all the demonstration
seeders loaded, so the references, totals, seat counts and revenue figures in them are real
records rather than mock-ups.

They are here so the system can be reviewed without installing it. To reproduce any of them,
see [Running it locally](../../README.md#running-it-locally); every demonstration account uses the
password `password`.

**58 screenshots, captured 20 September 2026.**

## Contents

- [C.1 Public pages](#c1-public-pages) — 9 screens
- [C.2 Visitor](#c2-visitor) — 7 screens
- [C.3 The central business rule refused](#c3-the-central-business-rule-refused) — 2 screens
- [C.4 Hotel staff](#c4-hotel-staff) — 6 screens
- [C.5 Ferry operator](#c5-ferry-operator) — 10 screens
- [C.6 Park staff](#c6-park-staff) — 10 screens
- [C.7 Administrator](#c7-administrator) — 14 screens

> **The negative cases matter most.** Most of the rules in this system are statements about
> what must *not* happen, so a screen on which nothing was refused does not evidence a rule.
> The refusals are in [C.3](#c3-the-central-business-rule-refused) (BR-01), C.5 (a pass
> refused at boarding) and C.7 (the last administrator cannot be deactivated).

## C.1 Public pages

The pages a visitor sees before signing in.

**C.1.1 — Home page, with the crossing search and what is on at the park**

![Home page, with the crossing search and what is on at the park](guest/01-home.jpg)

**C.1.2 — Visitor registration**

![Visitor registration](guest/02-register.jpg)

**C.1.3 — Sign in**

![Sign in](guest/03-login.jpg)

**C.1.4 — Interactive island map with the place directory**

![Interactive island map with the place directory](guest/04-island-map.jpg)

**C.1.5 — Hotel listing**

![Hotel listing](guest/05-hotels.jpg)

**C.1.6 — Hotel detail — room types, rates and occupancy**

![Hotel detail — room types, rates and occupancy](guest/06-hotel-detail.jpg)

**C.1.7 — What is on at the park, filtered by date and type**

![What is on at the park, filtered by date and type](guest/07-park-events.jpg)

**C.1.8 — Park event detail**

![Park event detail](guest/08-park-event-detail.png)

**C.1.9 — Ferry sailings, with seats remaining and fares**

![Ferry sailings, with seats remaining and fares](guest/09-ferry-schedules.jpg)

## C.2 Visitor

Signed in as a visitor holding a confirmed hotel booking. The ferry screens here are the positive half of BR-01; the refusal is the following section.

**C.2.1 — Book a hotel stay — hotel, dates, guests and room selection**

![Book a hotel stay — hotel, dates, guests and room selection](visitor/04-hotel-booking-create.png)

**C.2.2 — Hotel booking detail with its payment status**

![Hotel booking detail with its payment status](visitor/03-hotel-booking-detail.png)

**C.2.3 — Ferry purchase permitted at /ferry/schedules/2/book — the confirmed hotel booking that authorises it is named on the form**

![Ferry purchase permitted at /ferry/schedules/2/book — the confirmed hotel booking that authorises it is named on the form](visitor/05-ferry-book-allowed.jpg)

**C.2.4 — Ferry pass issued and paid, recorded against the authorising booking**

![Ferry pass issued and paid, recorded against the authorising booking](visitor/07-ferry-ticket-confirmed.png)

**C.2.5 — Buy park admissions — checkout**

![Buy park admissions — checkout](visitor/06-park-ticket-create.png)

**C.2.6 — Park admissions issued and paid**

![Park admissions issued and paid](visitor/08-park-ticket-confirmed.png)

**C.2.7 — My bookings — hotel stay, ferry crossing and park admissions in one place**

![My bookings — hotel stay, ferry crossing and park admissions in one place](visitor/02-my-bookings.png)

## C.3 The central business rule refused

BR-01 is the rule the brief is built around: no ferry ticket may be issued to a visitor who holds no qualifying hotel booking. The screen below is the same sailing and the same form as the one above, requested by a visitor with no booking.

**C.3.1 — BR-01 enforced at the same URL, /ferry/schedules/2/book — the purchase form is withheld and the visitor is directed to book a stay first. The difference between this screen and the one above is a server decision, not a hidden button**

![BR-01 enforced at the same URL, /ferry/schedules/2/book — the purchase form is withheld and the visitor is directed to book a stay first. The difference between this screen and the one above is a server decision, not a hidden button](visitor-blocked/01-ferry-book-refused.jpg)

**C.3.2 — My bookings — the same account, holding nothing**

![My bookings — the same account, holding nothing](visitor-blocked/02-my-bookings-empty.png)

## C.4 Hotel staff

Signed in as hotel staff.

**C.4.1 — Hotel staff dashboard — arrivals, departures and occupancy**

![Hotel staff dashboard — arrivals, departures and occupancy](hotel-staff/01-dashboard.png)

**C.4.2 — All hotel bookings, searchable and filterable**

![All hotel bookings, searchable and filterable](hotel-staff/02-bookings.png)

**C.4.3 — Room inventory across both hotels, by room type and status**

![Room inventory across both hotels, by room type and status](hotel-staff/03-rooms.png)

**C.4.4 — Room detail with its forward bookings**

![Room detail with its forward bookings](hotel-staff/04-room-detail.png)

**C.4.5 — Add a room**

![Add a room](hotel-staff/05-room-create.png)

**C.4.6 — Occupancy and revenue reporting for a chosen period**

![Occupancy and revenue reporting for a chosen period](hotel-staff/06-reports.png)

## C.5 Ferry operator

Signed in as a ferry operator. The counter issuance screen applies the same BR-01 check as the visitor-facing purchase, which is why it searches for a hotel booking rather than for a person.

**C.5.1 — Ferry operations dashboard**

![Ferry operations dashboard](ferry-operator/01-dashboard.png)

**C.5.2 — Sailing schedule management**

![Sailing schedule management](ferry-operator/02-schedules.png)

**C.5.3 — Add a sailing**

![Add a sailing](ferry-operator/03-schedule-create.png)

**C.5.4 — Issue a pass at the counter — the qualifying hotel booking is found by reference**

![Issue a pass at the counter — the qualifying hotel booking is found by reference](ferry-operator/04-issue-at-counter.png)

**C.5.5 — Pass issued at the counter and paid**

![Pass issued at the counter and paid](ferry-operator/09-counter-issue-confirmed.png)

**C.5.6 — Passenger manifest, showing the BR-02 seat limit that refuses a ticket once seats taken equals capacity**

![Passenger manifest, showing the BR-02 seat limit that refuses a ticket once seats taken equals capacity](ferry-operator/05-manifest.png)

**C.5.7 — Boarding check accepted — the pass is for today and is recorded against a confirmed stay**

![Boarding check accepted — the pass is for today and is recorded against a confirmed stay](ferry-operator/10-validate-valid.png)

**C.5.8 — Boarding check refused — a valid pass presented on the wrong day**

![Boarding check refused — a valid pass presented on the wrong day](ferry-operator/08-validate-result.png)

**C.5.9 — Boarding validation, awaiting a reference**

![Boarding validation, awaiting a reference](ferry-operator/06-validate.png)

**C.5.10 — Trip reports — sailings, seats sold, occupancy and revenue**

![Trip reports — sailings, seats sold, occupancy and revenue](ferry-operator/07-reports.png)

## C.6 Park staff

Signed in as park staff.

**C.6.1 — Park staff dashboard**

![Park staff dashboard](park-staff/01-dashboard.png)

**C.6.2 — Activity catalogue**

![Activity catalogue](park-staff/02-activities.png)

**C.6.3 — Activity detail with its scheduled events**

![Activity detail with its scheduled events](park-staff/03-activity-detail.png)

**C.6.4 — Add an activity**

![Add an activity](park-staff/04-activity-create.png)

**C.6.5 — Scheduled events with capacity taken against capacity**

![Scheduled events with capacity taken against capacity](park-staff/05-events.png)

**C.6.6 — Schedule an event**

![Schedule an event](park-staff/06-event-create.png)

**C.6.7 — Capacity monitoring — BR-06 refuses a sale once an event is full**

![Capacity monitoring — BR-06 refuses a sale once an event is full](park-staff/07-capacity.png)

**C.6.8 — Gate sale — selling admissions on site**

![Gate sale — selling admissions on site](park-staff/08-gate-sale.png)

**C.6.9 — On-site ticket validation**

![On-site ticket validation](park-staff/09-validate.png)

**C.6.10 — Attendance and sales reporting by channel and by activity**

![Attendance and sales reporting by channel and by activity](park-staff/10-reports.png)

## C.7 Administrator

Signed in as an administrator.

**C.7.1 — Administrator dashboard with cross-module statistics**

![Administrator dashboard with cross-module statistics](admin/01-dashboard.png)

**C.7.2 — User directory with search and role filter, showing each account status**

![User directory with search and role filter, showing each account status](admin/02-users.png)

**C.7.3 — Create a user account and assign a role**

![Create a user account and assign a role](admin/03-user-create.png)

**C.7.4 — User account detail with recorded activity**

![User account detail with recorded activity](admin/04-user-detail.png)

**C.7.5 — Edit a user and reassign a role**

![Edit a user and reassign a role](admin/05-user-edit.png)

**C.7.6 — An administrator account deactivated, leaving one active administrator**

![An administrator account deactivated, leaving one active administrator](admin/12-user-deactivated.png)

**C.7.7 — Deactivation refused — the system will not leave itself with no active administrator**

![Deactivation refused — the system will not leave itself with no active administrator](admin/13-last-admin-refused.png)

**C.7.8 — Consolidated reporting across hotel, ferry and park, with revenue by module**

![Consolidated reporting across hotel, ferry and park, with revenue by module](admin/06-reports.png)

**C.7.9 — Island map locations**

![Island map locations](admin/07-map-locations.png)

**C.7.10 — Add a map location, positioned on the island image**

![Add a map location, positioned on the island image](admin/08-map-location-create.jpg)

**C.7.11 — Map location detail**

![Map location detail](admin/09-map-location-detail.jpg)

**C.7.12 — Promotions across the modules**

![Promotions across the modules](admin/10-promotions.png)

**C.7.13 — Create a promotion**

![Create a promotion](admin/11-promotion-create.png)

**C.7.14 — Promotion detail**

![Promotion detail](admin/14-promotion-detail.png)
