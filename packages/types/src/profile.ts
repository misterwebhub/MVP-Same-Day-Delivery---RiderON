/** Profile/notifications types — app/Http/Resources/{Profile,SavedContact,Notification}Resource.php. */

export interface Profile {
  id: number;
  name: string | null;
  email: string | null;
  phone: string;
  role: string;
  preferred_language?: string | null;
  /** ISO 8601 or null. */
  created_at: string | null;
}

export interface UpdateProfilePayload {
  name?: string;
  email?: string | null;
}

export interface SavedContact {
  id: number;
  type: string;
  label: string | null;
  name: string;
  phone: string;
  station_id: number | null;
  landmark: string | null;
}

export interface CreateSavedContactPayload {
  type: string;
  label?: string | null;
  name: string;
  phone: string;
  station_id?: number | null;
  landmark?: string | null;
}

export interface AppNotification {
  id: number;
  type: string;
  title: string;
  body: string;
  data: unknown;
  channel: string;
  /** ISO 8601 or null. */
  read_at: string | null;
  /** ISO 8601 or null. */
  sent_at: string | null;
  /** ISO 8601 or null. */
  created_at: string | null;
}
