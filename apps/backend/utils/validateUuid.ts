import { validate } from 'uuid';

export const validateUuid = (value) => {
    if (!value) {
        return false;
    }
    else if (!value.includes('-')) {
        value = value.substr(0, 8) + '-' + value.substr(8, 4) + '-' + value.substr(12, 4) + '-' + value.substr(16, 4) + '-' + value.substr(20, 12)
    }
    return validate(value);
}