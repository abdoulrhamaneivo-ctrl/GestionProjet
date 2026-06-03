# Routes

Toutes les routes sont déclarées dans `app/routes.php` et utilisent le helper `url()`.

## Auth
- `GET /` , `/auth/login` : connexion
- `POST /auth/login` : traitement login
- `GET /auth/logout` : déconnexion

## Étudiant
- `GET /student/dashboard`
- `GET /student/submit` , `POST /student/submit`
- `GET /student/peer-gallery`
- `POST /student/peer-gallery/comment`
- `GET /student/report-pdf`

## Professeur
- `GET /prof/dashboard`
- `GET /prof/grade` , `POST /prof/grade`
- `POST /prof/publish-notes`
- `POST /prof/exercise/finish-corrections`
- `GET /prof/exercise/add-exemption`
- `POST /prof/exercise/add-exemption`
- `GET /prof/exercise/export-grades`
- `GET /prof/exercises/export-all-grades`

## Admin
- `GET /admin/dashboard`
- `GET /admin/exercise/create` , `POST /admin/exercise/create`
- `GET /admin/exercises`
- `GET /admin/exercises/edit` , `POST /admin/exercises/edit`
- `POST /admin/exercises/delete`
- `POST /admin/exercise/block`
- `GET /admin/exercise/export-grades`
- `GET /admin/exercises/export-all-grades`
- `GET /admin/group/manage` , `POST /admin/group/create`
- `POST /admin/exemption/add`
- `GET /admin/students/import` , `POST /admin/students/import`
- `GET /admin/students/import/download`
- `GET /admin/students/export`
- `GET /admin/students` , `GET /admin/students/create` , `POST /admin/students/create`
- `GET /admin/students/edit` , `POST /admin/students/edit`
- `POST /admin/students/delete`
