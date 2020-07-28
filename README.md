# Scanox - security services aggregator

# User story:
The service helps security companies to meet their customers.
There are two types of users:
a) Clients, who are looking for security services like home alarm signaling or physical security of the shop and so on
b) Security service providers (SSP) who are looking for clients

Clients have various ways to turn up at this service on the next different pages:
1) SSP's small personal landing page with description of their company and serfvices
2) Search results page in accordance client's request in Google or other search service
3) Main service page where clients can start search from the scratch

Security Service Providers can post information about yourself, describe types of services, prices and etc.
Maintenance their personal landing page and check search results for their potential users.

# Functionality and technical story:
1) Geopositioning based on IP - third party service
2) Geopositioning based on Geolocation API (coordinates) and reverse geocoding by the Gooogle Maps GeocodingAPI (adresses by coords)
3) Autocomplete addresses by user search input based on Google Maps Suggestion API.
    Due huge amount possible places covered by this service and several countries included in service scope, locations didn't store by the service in the begining of the service work. Locations data base was built dynamically by the new users looking for places (manually or automatically) and new service providers registered in the service in accordance Google places organization;
4) Search page by various criteria: nearest service providers who can give required scope of services and odered in accordance different conditions like as their profile.
5) Simple Service provider landing page
6) Authentication and role based athorization (service provider, moderator, admin) included credentials confirmation and password resets.
7) SSP user account with functionality:
    1) Profile - needs for search engine, landing page building, price calculator building
    2) Price calculator: constructor to build prices and conditions based on profile.
    3) Support form;
8) Moderator functions: approve SSP accounts, locks accounts, SSP profile corrections;
9) Admin functions: user roles management and administration, some portal statistic, marketing mailing interface
10) Mailing engine: transactional mails and marketing mailings. Used Mailgun mailing service.
11) SEO (search engine optimization):
    a) Pages title, headline and search engine content auto generation in accordance search words scope, search promotion and of course usability.
    b) Sitemap autogeneration:
        - SSP profiles
        - targeted search requests
    c) Server side rendering and pre-rendering with results caching in order to solve search engines indexing issue and fast response
    d) Recognition of the search robots requests in order to give them prerendered page.
    e) Front-end and back-end synced routings in accordance of different search requests (locations, conditions...).

# Tech stack:
1) Frontend: Bootstrap, Foundation for Emails (for responsive emails), JavaScript + JQuery + few small libs;
2) Backend - Apache http server, pure PHP with additional few libs and components, local Prerender NodeJS server for SSR.
3) DB - MySQL