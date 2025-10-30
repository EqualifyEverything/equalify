import { customAlphabet } from 'nanoid';

const letters = customAlphabet('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3);
const numbers = customAlphabet('0123456789', 3);

export const generateShortId = (): string => {
    return `${letters()}${numbers()}`;
}
