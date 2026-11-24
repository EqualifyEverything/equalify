import { useEffect, useRef, useState } from 'react';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import * as API from 'aws-amplify/api';
import { AuditTag } from './useAuditTags';

// One audit as the Audits screen lists it. `scans` holds the latest scan only
// (empty when the audit has never been scanned).
export interface AuditListScan {
    blockers_aggregate: { aggregate: { count: number } };
    status: string;
    percentage: number;
    updated_at: string;
}
export interface AuditListItem {
    id: string;
    created_at: string;
    name: string;
    interval: string;
    user: { name: string; email: string };
    scans: AuditListScan[];
    urls_aggregate: { aggregate: { count: number } };
    tag_assignments: { tag: AuditTag }[];
}
export interface AuditsPagination {
    page: number;
    pageSize: number;
    totalCount: number;
    totalPages: number;
}
interface AuditsPage {
    audits: AuditListItem[];
    pagination: AuditsPagination;
}

interface UseAuditsListOptions {
    /** Already trimmed to what should be searched for ("" for no search) */
    search: string;
    /** "user" for the signed-in person's audits, anything else for everyone's */
    createdBy: string;
    tagIds: string[];
    /** A column the backend knows how to sort by (see getAudits.ts); none = newest first */
    sortBy?: string;
    sortOrder?: 'asc' | 'desc';
    defaultPageSize: number;
    enabled?: boolean;
}

// The Audits screen's list, loaded from the backend one page at a time
// (GET /auth/getAudits), with the paging state that goes with it. The cards
// view and the table view each hold one of these.
//
// Two "pages" are in play while a request is in flight: `page` is the one
// that has been asked for, and `shown` describes the one on screen, which
// stays there until the new one arrives.
//
// The query key starts with "audits", so invalidating ["audits"] after an
// audit is created, changed or deleted refreshes the list.
export const useAuditsList = ({ search, createdBy, tagIds, sortBy, sortOrder, defaultPageSize, enabled = true }: UseAuditsListOptions) => {
    const list = {
        search,
        createdBy,
        tagIds: [...tagIds].sort(),
        sortBy: sortBy ?? null,
        sortOrder: sortBy ? sortOrder ?? 'asc' : null,
    };
    // A different search, filter or sort is a different list: start it from
    // its first page. Resetting here, during render, means the old page
    // number is never requested for the new list.
    const listKey = JSON.stringify(list);
    const [paging, setPaging] = useState({ page: 0, pageSize: defaultPageSize, listKey });
    if (paging.listKey !== listKey) setPaging({ page: 0, pageSize: paging.pageSize, listKey });
    const page = paging.listKey === listKey ? paging.page : 0;
    const pageSize = paging.pageSize;

    const query = useQuery({
        queryKey: ['audits', 'page', { ...list, page, pageSize }],
        queryFn: async () => {
            const queryParams: Record<string, string> = {
                page: String(page),
                pageSize: String(pageSize),
                createdBy: list.createdBy,
            };
            if (list.search) queryParams.search = list.search;
            if (list.tagIds.length > 0) queryParams.tagIds = list.tagIds.join(',');
            if (list.sortBy && list.sortOrder) {
                queryParams.sortBy = list.sortBy;
                queryParams.sortOrder = list.sortOrder;
            }
            const response = (await (
                await API.get({ apiName: 'auth', path: '/getAudits', options: { queryParams } }).response
            ).body.json()) as any;
            if (response?.status !== 'success') {
                throw new Error(response?.message ?? 'Failed to load audits');
            }
            return response as AuditsPage;
        },
        // Keep the page on screen until the next one arrives, rather than
        // dropping back to a loading skeleton on every page turn or keystroke
        placeholderData: keepPreviousData,
        enabled,
    });

    // True once the audits on screen are the ones that were asked for
    const settled = !!query.data && !query.isPlaceholderData;

    // A request that fails for a page never seen before leaves nothing to
    // show for it. Keep the last page of this same list on screen instead,
    // with its pager, so a failed page turn is something to retry or step
    // back from rather than an empty screen.
    const lastLoaded = useRef<{ listKey: string; data: AuditsPage } | null>(null);
    useEffect(() => {
        if (settled) lastLoaded.current = { listKey, data: query.data! };
    }, [settled, query.data, listKey]);
    const kept = !query.data && query.isError && lastLoaded.current?.listKey === listKey ? lastLoaded.current.data : undefined;
    const data = query.data ?? kept;
    const shown = data?.pagination;

    // If the list shrank (audits deleted, say) and the page asked for no
    // longer exists, the backend sends its last page instead. Follow it, so
    // Previous and Next count from the page that is actually on screen. Only
    // once the answer is fresh: an answer remembered from before a refetch
    // may describe a list that has since grown again.
    const servedPage = settled && !query.isFetching ? query.data!.pagination.page : null;
    useEffect(() => {
        if (servedPage !== null && servedPage !== page) setPaging({ page: servedPage, pageSize, listKey });
    }, [servedPage, page, pageSize, listKey]);

    const totalCount = shown?.totalCount ?? 0;
    // Counted against the page size that has been chosen, which can differ
    // from the size of the page still on screen
    const pageCount = Math.max(1, Math.ceil(totalCount / pageSize));

    return {
        audits: data?.audits,
        /** Paging of the audits on screen (undefined until the first page loads) */
        shown,
        settled,
        totalCount,
        /**
         * The page to count Previous and Next from, zero-based: the one that
         * has been asked for, or, when that request failed, the one still on
         * screen (so Next asks for the failed page again). Always in pages of
         * the size now chosen, which the page still on screen may not be.
         */
        page: kept ? Math.floor((kept.pagination.page * kept.pagination.pageSize) / pageSize) : page,
        pageSize,
        pageCount,
        /** No page has loaded yet */
        isPending: query.isPending,
        isError: query.isError,
        /** Returns false, and does nothing, when already on (or heading to) that page */
        goToPage: (target: number) => {
            const next = Math.min(Math.max(0, target), pageCount - 1);
            if (next === page) {
                if (!query.isError) return false;
                // The page that failed to load: ask for it again
                query.refetch();
                return true;
            }
            setPaging({ page: next, pageSize, listKey });
            return true;
        },
        /** Stays on the page that holds the first audit now on screen */
        changePageSize: (size: number) => {
            const firstShown = shown ? shown.page * shown.pageSize : 0;
            setPaging({ page: Math.floor(firstShown / size), pageSize: size, listKey });
        },
    };
};
