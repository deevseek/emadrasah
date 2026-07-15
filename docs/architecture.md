# Architecture

E-Madrasah adalah modular monolith Laravel 12. Domain awal: Identity & Access, School Profile, Academic Period, Settings, Audit, dan UI Shell. Pola wajib: thin controller, Form Request, Policy/middleware, Service/Action, PHP Enum, Event/Listener, Job untuk proses berat, transaction untuk aktivasi periode, dan test otomatis.
