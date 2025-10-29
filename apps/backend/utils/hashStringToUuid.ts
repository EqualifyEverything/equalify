import { createHash } from 'crypto';

export const hashStringToUuid = (input) => {
    const hash1 = createHash('sha256').update(input).digest();
    const hash2 = createHash('sha256').update(hash1).digest('hex');
    return hash2.slice(0, 32);
}