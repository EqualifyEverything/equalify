import { useQuery } from '@tanstack/react-query';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
// Bundled copy of the repo-root WHATS_NEW.md, used until (or if) the live copy loads
import bundledWhatsNew from '../../../../WHATS_NEW.md?raw';

export interface WhatsNewEntry {
    id: string;
    title: string;
    date: string;
    html: string;
}

// Fetched from the deployed branch so the panel can be updated without a redeploy.
// Set VITE_WHATS_NEW_URL to override, or to an empty string to use only the bundled copy.
const remoteUrl = import.meta.env.VITE_WHATS_NEW_URL
    ?? (import.meta.env.VITE_BRANCH
        ? `https://raw.githubusercontent.com/EqualifyEverything/equalify/${import.meta.env.VITE_BRANCH}/WHATS_NEW.md`
        : '');

export const parseWhatsNew = (raw: string): WhatsNewEntry | null => {
    const match = raw.replace(/\r\n/g, '\n').match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
    if (!match) return null;

    const meta: Record<string, string> = {};
    for (const line of match[1].split('\n')) {
        const separator = line.indexOf(':');
        if (separator === -1) continue;
        meta[line.slice(0, separator).trim()] = line
            .slice(separator + 1)
            .trim()
            .replace(/^(["'])(.*)\1$/, '$2');
    }

    const body = match[2].trim();
    if (!meta.title || !meta.date || !body) return null;

    return {
        id: `${meta.date}|${meta.title}`,
        title: meta.title,
        date: meta.date,
        html: DOMPurify.sanitize(marked.parse(body) as string),
    };
};

const bundledEntry = parseWhatsNew(bundledWhatsNew);

export const useWhatsNew = () => useQuery({
    queryKey: ['whatsNew'],
    queryFn: async () => {
        const response = await fetch(remoteUrl);
        if (!response.ok) throw new Error(`Failed to fetch What's New (${response.status})`);
        const entry = parseWhatsNew(await response.text());
        if (!entry) throw new Error("Remote WHATS_NEW.md is malformed");
        return entry;
    },
    // Show the bundled copy immediately; on a failed fetch the query keeps it as data
    initialData: bundledEntry ?? undefined,
    initialDataUpdatedAt: 0,
    enabled: !!remoteUrl,
    staleTime: 60 * 60 * 1000,
    retry: false,
    refetchOnWindowFocus: false,
});
