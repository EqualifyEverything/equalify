export interface StreamResults {
    id: string;
    blockers: Blocker[];
    date: string
    message: string
}

export interface Blocker {
    source: string; // "axe-core"|"editoria11y"|"pdf-scan"
    test: string;
    tags?: (string)[] | null;
    description: string;
    summary: string;
    node: string | null;
}