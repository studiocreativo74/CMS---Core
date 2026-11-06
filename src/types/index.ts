// --- Datentypen & Interfaces der Bootsfahrschule, Charter & Academy ---

export interface Instructor {
  id: number;
  name: string;
  title: string;
  email: string;
  phone: string;
  public_mobile: string;
  is_mobile_public: boolean;
  color_code: string;
  pay_rate_type: 'per_hour' | 'per_appointment';
  pay_rate_amount: number;
  payroll_external_code: string;
  license_number: string;
  // HR Details
  ahv_number?: string;
  bank_iban?: string;
  bank_name?: string;
  bank_city?: string;
  address?: string;
  hourly_rate?: number;
}

export interface Appointment {
  id: number;
  date: string;
  start_time: string;
  end_time: string;
  duration_minutes: number;
  service_name: string;
  boat_name: string;
  location_name: string;
  customer_id: number;
  customer_name: string;
  customer_email: string;
  customer_phone: string;
  instructor_id: number;
  instructor_name: string;
  status: 'requested' | 'confirmed' | 'completed' | 'cancelled_customer';
  price: number;
  instructor_payout_amount: number;
  instructor_payout_status: 'open' | 'exported' | 'paid';
  instructor_payout_note: string;
}

export interface CoachAvailability {
  id: number;
  instructor_id: number;
  date: string;
  start_time?: string;
  end_time?: string;
  type: 'vacation' | 'sick' | 'military' | 'unavailable' | 'available';
  note: string;
}

export interface CoachWorkingHour {
  id: number;
  instructor_id: number;
  date: string;
  start_time: string;
  end_time: string;
  break_minutes: number;
  total_hours: number;
  rate_amount: number;
  total_amount: number;
  category: 'lesson' | 'exam' | 'admin' | 'maintenance' | 'other';
  status: 'draft' | 'submitted' | 'approved' | 'exported';
  note: string;
}

export interface AccountRequest {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  desired_role: 'instructor' | 'student' | 'renter' | 'admin';
  assigned_profile?: 'instructor' | 'student' | 'renter' | 'admin';
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
  approved_at?: string;
  admin_notes?: string;
  allergies?: string;
  notes: string;
}

export interface ServicePriceOption {
  id: number;
  service_id: number;
  duration: number; // Minuten pro Einheit (z. B. 60, 90, 120, 180 Min)
  price: number; // Preis in CHF
  sessions_count: number; // 1 bei Einzellektion, 5 bei 5er Abo, 10 bei 10er Abo
  sort_order: number;
}

export interface ServiceOffer {
  id: number;
  name: string;
  description: string;
  offer_type: 'bootsfahrschule' | 'coaching' | 'mieten' | 'digital' | 'anderes';
  category?: string;
  target_audience: string;
  image_url: string;
  is_active: boolean;
  is_subscription: boolean;
  sessions_count: number;
  location_ids: string[]; // ["loc_luzern", "loc_stansstad", "loc_hergiswil", ...]
  price_options: ServicePriceOption[];
}

export interface CustomerSubscription {
  id: number;
  customer_id: number;
  service_id: number;
  package_name: string;
  total_sessions: number;
  used_sessions: number;
  purchased_at: string;
  expires_at: string;
  price_paid: number;
  payment_method: 'bank_transfer' | 'invoice' | 'qr_bill';
  payment_status: 'open' | 'paid';
  order_id: string;
  location_name?: string;
  created_at?: string;
  is_digital_elearning?: boolean;
}

export interface ElearningVideo {
  id: number;
  title: string;
  category: 'maneuver' | 'safety' | 'physics' | 'knots' | 'navigation';
  categoryLabel: string;
  duration: string; // z.B. '14:20'
  durationSeconds: number;
  thumbnail: string;
  difficulty: 'Einsteiger' | 'Fortgeschritten' | 'Experte';
  description: string;
  chapters: { title: string; time: string; seconds: number }[];
  keyTakeaways: string[];
  bsvRef?: string;
}

export interface ElearningChart {
  id: number;
  title: string;
  subtitle: string;
  category: string;
  imageUrl: string;
  bsvArticle: string;
  description: string;
  keyPoints: string[];
  tips: string;
}

export interface ElearningQuizQuestion {
  id: number;
  question: string;
  options: string[];
  correctIndex: number;
  explanation: string;
  lawRef: string;
}

export interface PromoCode {
  id: number;
  code: string;
  description: string;
  discount_type: 'percentage' | 'fixed';
  discount_value: number; // 10.00 (%) oder 50.00 (CHF)
  min_order_amount: number | null;
  max_uses: number | null;
  used_count: number;
  valid_from: string | null;
  valid_until: string | null;
  is_active: boolean;
  applicable_service_ids?: number[] | null;
}

export interface Boat {
  id: number;
  name: string;
  type: string;
  registration: string;
  berth: string; // Liegeplatz & Hafen
  status: 'available' | 'occupied' | 'maintenance';
  fuel_capacity: number; // z.B. 160 Liter
  current_fuel_percent: number; // 0 - 100
  hourly_consumption: number; // Ø Verbrauch L/h
  telemetry_gateway_id: string; // NMEA 2000 Gateway ID
  image_url: string;
  current_session?: {
    session_id: string;
    check_in_code: string;
    customer_id: number;
    customer_name: string;
    customer_phone: string;
    appointment_id: number;
    start_time: string;
    start_engine_hours: number;
  } | null;
}

export interface BoatCheckin {
  id: number;
  appointment_id: number;
  customer_id: number;
  boat_id: number;
  boat_name: string;
  checked_in_at: string;
  check_in_code: string;
  fitness_confirmed: boolean;
  license_confirmed: boolean;
  safety_confirmed: boolean;
  emergency_contact_confirmed: boolean;
  boat_condition_confirmed: boolean;
  boat_damage_reported: boolean;
  boat_damage_photos: string[];
  boat_damage_notes: string;
  telemetry_session_id: string;
  external_script_triggered: boolean;
  external_script_message: string;
}

export interface BoatCheckout {
  id: number;
  check_in_id: number;
  appointment_id: number;
  customer_id: number;
  boat_id: number;
  boat_name: string;
  checked_out_at: string;
  duration_minutes: number;
  fuel_status: 'full' | 'refueled' | 'needs_fuel' | 'not_full';
  liters_refueled: number;
  fuel_receipt_photo?: string;
  fuel_gauge_photo?: string;
  sensor_fuel_liters_used: number;
  sensor_engine_hours: number;
  fuel_cost_total: number;
  fuel_price_per_liter: number;
  return_condition_confirmed: boolean;
  return_damage_reported: boolean;
  return_damage_photos: string[];
  return_damage_notes: string;
  cleanliness_rating: 'clean' | 'moderate' | 'dirty';
  mooring_secured: boolean;
  keys_returned: boolean;
  equipment_checked: boolean;
  external_script_queried: boolean;
  external_script_data?: {
    fuel_liters_used: number;
    engine_hours: number;
    avg_rpm: number;
    max_rpm: number;
    coolant_temp_c: number;
    battery_v: number;
    nmea_signature: string;
  };
}

export interface CmsBlock {
  id: number;
  type: 'hero' | 'features' | 'two_column' | 'cta' | 'text';
  title: string;
  subtitle: string;
  content: string;
  sort_order: number;
  is_visible: boolean;
  extra?: any;
}

export type AnnouncementTargetRole = 'coach' | 'customer';

export interface Announcement {
  id: number;
  title: string;
  content: string;
  target_roles: AnnouncementTargetRole[];
  author_id: number;
  author_name?: string;
  is_active: boolean;
  created_at: string;
  updated_at?: string;
}

export interface AnnouncementRead {
  id: number;
  announcement_id: number;
  user_id: number;
  user_name: string;
  user_role: AnnouncementTargetRole | 'admin';
  read_at: string;
}

