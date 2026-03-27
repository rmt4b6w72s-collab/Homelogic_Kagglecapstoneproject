/**
 * Aligns with App\Constants\UserRoles::CAREGIVER_ROLES
 */
export const CAREGIVER_ROLES = [
    'caregiver',
    'care_giver',
    'nurse',
    'registered_nurse',
    'licensed_nurse',
];

export function isCaregiverRole(role) {
    if (!role) return false;
    return CAREGIVER_ROLES.includes(String(role).toLowerCase().trim());
}
