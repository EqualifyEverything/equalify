import { createHash } from 'crypto';

export const hashStringToUuid = (input) => {
    return createHash('md5').update(input).digest('hex');
}