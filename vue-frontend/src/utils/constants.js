// Asset statuses
export const ASSET_STATUS = {
  IN_STOCK: 'in_stock',
  ASSIGNED: 'assigned',
  DEPLOYED: 'deployed',
  FAULTY: 'faulty',
  RETIRED: 'retired',
  MAINTENANCE: 'maintenance'
}

export const ASSET_STATUS_LABELS = {
  in_stock: 'In Stock',
  assigned: 'Assigned',
  deployed: 'Deployed',
  faulty: 'Faulty',
  retired: 'Retired',
  maintenance: 'Maintenance'
}

export const ASSET_STATUS_COLORS = {
  in_stock: 'success',
  assigned: 'info',
  deployed: 'info',
  faulty: 'danger',
  retired: 'secondary',
  maintenance: 'warning'
}

// Movement types
export const MOVEMENT_TYPE = {
  IN: 'in',
  OUT: 'out',
  TRANSFER: 'transfer',
  RETURN: 'return',
  ADJUSTMENT: 'adjustment'
}

export const MOVEMENT_TYPE_LABELS = {
  in: 'Entry',
  out: 'Exit',
  transfer: 'Transfer',
  return: 'Return',
  adjustment: 'Adjustment'
}

// Fiber types
export const FIBER_TYPE = {
  SINGLE_MODE: 'single_mode',
  MULTI_MODE: 'multi_mode'
}

export const FIBER_TYPE_LABELS = {
  single_mode: 'Single Mode',
  multi_mode: 'Multi Mode'
}

// Alert severities
export const ALERT_SEVERITY = {
  INFO: 'info',
  WARNING: 'warning',
  CRITICAL: 'critical'
}

// User roles
export const USER_ROLES = {
  SUPER_ADMIN: 'super_admin',
  ADMIN: 'admin',
  TECHNICIAN: 'technician',
  READER: 'reader'
}

export const USER_ROLE_LABELS = {
  super_admin: 'Super Admin',
  admin: 'Administrator',
  technician: 'Technician',
  reader: 'Reader'
}
