# 02 — Database Schema (MySQL 8)

Conventions: `id BIGINT UNSIGNED AUTO_INCREMENT PK`, `created_at`/`updated_at` on every table, `deleted_at` (soft delete) where noted, `utf8mb4`, InnoDB. Money stored as `INT` paise (avoid float rounding). All `*_id` FKs indexed; FKs use `RESTRICT` on delete unless noted.

## Identity & profiles

**users** — polymorphic base auth record (mirrors Laravel default, extended)
`id, name, email NULLABLE UNIQUE, phone VARCHAR(15) UNIQUE, phone_verified_at, password NULLABLE, role ENUM('customer','partner','admin','ops','support'), status ENUM('active','suspended','deleted') DEFAULT 'active', last_login_at, created_at, updated_at, deleted_at`
Index: `(phone)`, `(role, status)`

**customer_profiles**
`id, user_id FK→users UNIQUE, preferred_language ENUM('en','hi') DEFAULT 'en', email_verified_at, created_at, updated_at`

**saved_contacts** (saved sender/receiver presets, Profile → "saved sender/receiver details")
`id, customer_id FK→users, type ENUM('sender','receiver'), label VARCHAR(50), name, phone, station_id FK→stations NULLABLE, landmark VARCHAR(255), created_at, updated_at, deleted_at`

**delivery_partners**
`id, user_id FK→users UNIQUE, partner_code VARCHAR(20) UNIQUE, photo_url, vehicle_type ENUM('train','bus','bike','on_foot') , id_proof_type, id_proof_number_encrypted, verification_status ENUM('pending','verified','rejected') DEFAULT 'pending', is_active BOOLEAN DEFAULT 1, rating_avg DECIMAL(3,2) DEFAULT 0, completed_deliveries_count INT UNSIGNED DEFAULT 0, current_home_city_id FK→cities, created_at, updated_at, deleted_at`
Index: `(verification_status, is_active)`

## Geography / routes

**cities**
`id, name VARCHAR(100), state VARCHAR(100), is_active BOOLEAN DEFAULT 1, created_at, updated_at, deleted_at`
Unique: `(name, state)`

**stations**
`id, city_id FK→cities, name VARCHAR(150) e.g. "Kanpur Central", code VARCHAR(10) e.g. "CNB", type ENUM('railway','bus_stand'), latitude DECIMAL(10,7) NULLABLE, longitude DECIMAL(10,7) NULLABLE, address TEXT NULLABLE, is_active BOOLEAN DEFAULT 1, created_at, updated_at, deleted_at`
Unique: `(code)`. Index: `(city_id, is_active)`

**routes**
`id, origin_station_id FK→stations, destination_station_id FK→stations, distance_km DECIMAL(6,2), estimated_duration_minutes INT UNSIGNED, cutoff_time TIME (last booking time same-day), max_parcels_per_schedule INT UNSIGNED DEFAULT 50, waiting_time_minutes INT UNSIGNED DEFAULT 30 (receiver wait at destination), is_active BOOLEAN DEFAULT 1, created_at, updated_at, deleted_at`
Unique: `(origin_station_id, destination_station_id)`. Index: `(is_active)`
Note: Kanpur→Lucknow and Lucknow→Kanpur are two separate rows — keeps direction-specific cutoff/pricing/duration independent.

**route_schedules** (departure windows per route, e.g. 10:00→13:30)
`id, route_id FK→routes, departure_time TIME, arrival_time TIME, days_of_week SET('mon','tue','wed','thu','fri','sat','sun') or JSON array, booking_cutoff_minutes_before INT UNSIGNED (minutes before departure that booking closes), is_active BOOLEAN DEFAULT 1, created_at, updated_at`
Index: `(route_id, is_active)`

**route_schedule_dates** (Phase 2 — schedule overrides/holidays; blackout dates)
`id, route_schedule_id FK, date DATE, is_cancelled BOOLEAN DEFAULT 0, reason VARCHAR(255) NULLABLE`

## Pricing

**pricing_rules** (versioned, effective-dated so history is preserved for past orders)
`id, route_id FK→routes NULLABLE (NULL = default/global rule), rule_type ENUM('base','weight_slab','peak_hour','platform_fee','tax'), min_weight_grams INT UNSIGNED NULLABLE, max_weight_grams INT UNSIGNED NULLABLE, amount_paise INT UNSIGNED NULLABLE, percentage DECIMAL(5,2) NULLABLE, effective_from DATETIME, effective_to DATETIME NULLABLE, is_active BOOLEAN DEFAULT 1, created_at, updated_at`
Index: `(route_id, rule_type, is_active, effective_from)`

**coupons** (Phase 2)
`id, code VARCHAR(30) UNIQUE, discount_type ENUM('flat','percentage'), value DECIMAL(8,2), max_discount_paise INT UNSIGNED NULLABLE, min_order_amount_paise INT UNSIGNED NULLABLE, usage_limit_total INT UNSIGNED NULLABLE, usage_limit_per_user INT UNSIGNED DEFAULT 1, valid_from, valid_until, is_active BOOLEAN DEFAULT 1, created_at, updated_at`

## Orders / parcels

**orders**
`id, booking_reference VARCHAR(20) UNIQUE (e.g. RID-KL-8F3K2Q, shown to users instead of id), customer_id FK→users, route_id FK→routes, route_schedule_id FK→route_schedules, partner_id FK→delivery_partners NULLABLE, status VARCHAR(40) (state machine value, see doc 04), booking_date DATE, sender_name, sender_phone VARCHAR(15), sender_landmark VARCHAR(255), receiver_name, receiver_phone VARCHAR(15), receiver_landmark VARCHAR(255), price_breakdown JSON (frozen snapshot at booking time), total_amount_paise INT UNSIGNED, currency CHAR(3) DEFAULT 'INR', prohibited_items_declared_at DATETIME, cancelled_at DATETIME NULLABLE, cancellation_reason VARCHAR(255) NULLABLE, cancelled_by ENUM('customer','partner','admin','system') NULLABLE, arrived_destination_at DATETIME NULLABLE, waiting_deadline_at DATETIME NULLABLE, delivered_at DATETIME NULLABLE, completed_at DATETIME NULLABLE, idempotency_key VARCHAR(64) UNIQUE NULLABLE, created_at, updated_at, deleted_at`
Index: `(customer_id, status)`, `(partner_id, status)`, `(route_id, booking_date)`, `(status)`

**order_status_history** (append-only audit trail — every transition)
`id, order_id FK→orders, from_status VARCHAR(40) NULLABLE, to_status VARCHAR(40), changed_by_type ENUM('customer','partner','admin','system'), changed_by_id BIGINT UNSIGNED NULLABLE, metadata JSON NULLABLE, created_at`
Index: `(order_id, created_at)`

**parcels** (1:1 with order for MVP, modeled separately for future multi-parcel orders)
`id, order_id FK→orders UNIQUE, parcel_type ENUM('documents','clothing','electronics','gifts','books','other'), weight_slab ENUM('upto_1kg','1_3kg','3_5kg','5_10kg'), quantity INT UNSIGNED DEFAULT 1, declared_value_paise INT UNSIGNED NULLABLE, special_instructions TEXT NULLABLE, created_at, updated_at`

**parcel_images**
`id, parcel_id FK→parcels, storage_path VARCHAR(255), created_at`

## Payments

**payments** (one logical payment intent per order attempt; supports retries)
`id, order_id FK→orders, provider ENUM('razorpay','mock'), provider_order_id VARCHAR(100) NULLABLE, amount_paise INT UNSIGNED, currency CHAR(3) DEFAULT 'INR', status ENUM('created','pending','success','failed','cancelled') DEFAULT 'created', idempotency_key VARCHAR(64) UNIQUE, created_at, updated_at`
Index: `(order_id, status)`

**payment_transactions** (immutable log of every gateway event/webhook/callback received — supports duplicate-callback detection)
`id, payment_id FK→payments, provider_payment_id VARCHAR(100) NULLABLE, provider_signature VARCHAR(255) NULLABLE, event_type VARCHAR(50) (e.g. 'checkout_callback','webhook_captured','webhook_failed'), raw_payload JSON, signature_verified BOOLEAN DEFAULT 0, processed BOOLEAN DEFAULT 0, created_at`
Unique: `(provider_payment_id, event_type)` to hard-block duplicate webhook processing. Index: `(payment_id)`

**refunds**
`id, order_id FK→orders, payment_id FK→payments, requested_by ENUM('customer','admin','system'), reason VARCHAR(255), amount_paise INT UNSIGNED, status ENUM('pending','approved','processing','completed','rejected') DEFAULT 'pending', provider_refund_id VARCHAR(100) NULLABLE, approved_by BIGINT UNSIGNED NULLABLE (admin user id), created_at, updated_at`

## OTP

**otp_verifications** — covers login OTP AND pickup/delivery OTP via `purpose`
`id, order_id FK→orders NULLABLE (NULL for login OTPs), user_id FK→users NULLABLE (target phone owner for login), purpose ENUM('login','pickup','delivery'), phone VARCHAR(15), otp_hash VARCHAR(255) (hashed, never plaintext), expires_at DATETIME, verified_at DATETIME NULLABLE, attempt_count TINYINT UNSIGNED DEFAULT 0, max_attempts TINYINT UNSIGNED DEFAULT 5, locked_until DATETIME NULLABLE, resend_count TINYINT UNSIGNED DEFAULT 0, last_sent_at DATETIME, created_at, updated_at`
Index: `(order_id, purpose)`, `(phone, purpose, created_at)`
Never logged in plaintext anywhere (app logs, DB, queue payloads all reference `otp_verifications.id` only). Redis mirrors `locked_until`/attempt counters for fast rate-limit checks.

**otp_verification_logs** (audit — every verify attempt, pass or fail, no OTP value stored)
`id, otp_verification_id FK, attempted_by_type ENUM('customer','partner'), attempted_by_id BIGINT UNSIGNED, result ENUM('success','invalid','expired','locked'), ip_address VARCHAR(45) NULLABLE, created_at`

## Notifications / support

**notifications**
`id, user_id FK→users, type VARCHAR(50) (e.g. 'rider_assigned','pickup_completed'), title VARCHAR(150), body TEXT, data JSON NULLABLE (deep-link payload), channel ENUM('push','sms','whatsapp','in_app'), read_at DATETIME NULLABLE, sent_at DATETIME NULLABLE, created_at`
Index: `(user_id, read_at)`

**support_tickets**
`id, ticket_number VARCHAR(20) UNIQUE, customer_id FK→users, order_id FK→orders NULLABLE, category ENUM('payment','pickup','delivery','rider','wrong_parcel','damaged_parcel','receiver_unavailable','cancellation','refund','other'), description TEXT, status ENUM('open','in_progress','resolved','closed') DEFAULT 'open', assigned_to BIGINT UNSIGNED NULLABLE (admin user id), resolved_at DATETIME NULLABLE, created_at, updated_at`
Index: `(customer_id)`, `(status)`

**support_ticket_messages** (Phase 2 chat)
`id, ticket_id FK, sender_type ENUM('customer','admin'), sender_id BIGINT UNSIGNED, message TEXT, created_at`

## Configuration / compliance

**prohibited_items**
`id, name VARCHAR(150), category VARCHAR(100), description TEXT NULLABLE, is_active BOOLEAN DEFAULT 1, created_at, updated_at`

**order_declarations** (records acceptance, not just a frontend checkbox)
`id, order_id FK→orders UNIQUE, prohibited_items_version_id FK→prohibited_items_versions (see below) NULLABLE, accepted_at DATETIME, ip_address VARCHAR(45), device_info VARCHAR(255) NULLABLE`

**prohibited_items_versions** (so we know exactly which list text a customer agreed to)
`id, version_label VARCHAR(20), content JSON (snapshot of prohibited_items at publish time), published_at DATETIME`

**app_settings** (key/value, admin-editable business policy — waiting time defaults, auto-complete grace period, etc.)
`id, key VARCHAR(100) UNIQUE, value JSON, description VARCHAR(255) NULLABLE, updated_by BIGINT UNSIGNED NULLABLE, updated_at`

**audit_logs** (generic admin/system action log, separate from order_status_history)
`id, actor_type ENUM('admin','system'), actor_id BIGINT UNSIGNED NULLABLE, action VARCHAR(100), auditable_type VARCHAR(100), auditable_id BIGINT UNSIGNED, changes JSON NULLABLE, ip_address VARCHAR(45) NULLABLE, created_at`
Index: `(auditable_type, auditable_id)`

## Analytics (Phase 2, lightweight now)

**analytics_events**
`id, event_name VARCHAR(60), user_id FK→users NULLABLE, order_id FK→orders NULLABLE, properties JSON NULLABLE, created_at`
Index: `(event_name, created_at)`

---

### ERD summary (text form)

```
cities 1───n stations 1───n routes(origin) 
                        n routes(destination)───1 stations
routes 1───n route_schedules 1───n route_schedule_dates
routes 1───n pricing_rules

users 1───1 customer_profiles
users 1───1 delivery_partners
users 1───n saved_contacts
users 1───n orders (as customer)
delivery_partners 1───n orders

orders 1───1 parcels 1───n parcel_images
orders 1───n order_status_history
orders 1───n payments 1───n payment_transactions
orders 1───n refunds
orders 1───n otp_verifications 1───n otp_verification_logs
orders 1───1 order_declarations
orders 1───n support_tickets
orders 1───n notifications (indirectly via user_id)
```

All money in `_paise` integer columns; format to ₹ only at API/UI edge. All phone numbers stored E.164-ish (`+91XXXXXXXXXX`) and masked (`+91 98XX XX43 21`) before reaching the *other party's* client — the owning user always sees their own number unmasked.
