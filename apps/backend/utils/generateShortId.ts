import { customAlphabet } from 'nanoid';

const letters = customAlphabet('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 4);
const numbers = customAlphabet('0123456789', 4);

export const generateShortId = (): string => {
    return `${letters()}${numbers()}`;
}
