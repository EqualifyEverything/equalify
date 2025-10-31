import { useQuery } from '@tanstack/react-query';
import { useReactTable, getCoreRowModel, flexRender, ColumnDef } from '@tanstack/react-table';
import * as API from 'aws-amplify/api';
import { useState, useMemo } from 'react';
import { formatDate } from '../utils';
import * as ToggleGroup from "@radix-ui/react-toggle-group";
import Select, { MultiValue } from 'react-select'

interface BlockerTag {
    id: string;
    content: string;
}

interface Blocker {
    id: string;
    short_id: string;
    created_at: string;
    url: string;
    url_id: string;
    content: string;
    equalified: boolean;
    messages: string[];
    tags: BlockerTag[];
    categories: string[];
}

interface BlockersTableProps {
    auditId: string;
}

interface Option {
  value: string;
  label: string;
}

export const BlockersTable = ({ auditId }: BlockersTableProps) => {
    const [page, setPage] = useState(0);
    const [pageSize] = useState(50);
    
    const [selectedTags, setSelectedTags] = useState<Option[]>([]);
    const [availableTags, setAvailableTags] = useState<Option[]>([]); // Added to prevent content flicker while fetching
    
    const [selectedCategories, setSelectedCategories] = useState<Option[]>([]);
    const [availableCategories, setAvailableCategories] = useState<Option[]>([]);  // Added to prevent content flicker while fetching

    const [selectedStatus, setSelectedStatus] = useState<string>('active');

    const { data, isFetching, isLoading, error } = useQuery({
        queryKey: ['auditBlockers', auditId, page, pageSize, selectedTags, selectedCategories, selectedStatus],
        queryFn: async () => {
            const params: Record<string, string> = {
                id: auditId,
                page: page.toString(),
                pageSize: pageSize.toString(),
            };
            if (selectedTags.length > 0) {
                //params.tags = selectedTags.join(',');
                params.tags = selectedTags.map((tag)=>tag.value).join(",");
            }
            if (selectedCategories.length > 0) {
                params.types = selectedCategories.map((tag)=>tag.value).join(',');
            }
            if (selectedStatus) {
                params.status = selectedStatus;
            }
            const response = await API.get({
                apiName: 'auth',
                path: '/getAuditTable',
                options: { queryParams: params }
            }).response;
            const resp = await response.body.json() as any;

            // we need to parse the server data to convert BlockerTag[] to Options[]
            resp.availableTags = resp.availableTags?.map((tag:BlockerTag)=>({value:tag.id, label:tag.content}));
            // Then we store it in local state
            setAvailableTags(resp.availableTags);
            
            resp.availableCategories = resp.availableCategories?.map((category:string) => ({value: category, label: category}));
            setAvailableCategories(resp.availableCategories);

            return resp;
        },
        refetchInterval: 5000,
    });

    const columns = useMemo<ColumnDef<Blocker>[]>(() => [
        {
            accessorKey: 'short_id',
            header: 'ID',
            cell: ({ getValue }) => {
                const shortId = getValue() as string;
                return (
                    <code className='text-sm font-bold bg-gray-100 px-2 py-1 rounded'>
                        {shortId || 'N/A'}
                    </code>
                );
            },
        },
        {
            accessorKey: 'content',
            header: 'Blocker Code',
            cell: ({ getValue }) => {
                const content = getValue() as string;
                return (
                    <code className='text-xs break-all block max-w-md'>
                        {content.length > 100 ? content.substring(0, 100) + '...' : content}
                    </code>
                );
            },
        },
        {
            accessorKey: 'url',
            header: 'URL',
            cell: ({ getValue }) => {
                const url = getValue() as string;
                return (
                    <a 
                        href={url} 
                        target='_blank' 
                        rel='noopener noreferrer'
                        className='text-blue-600 hover:underline break-all block max-w-xs'
                    >
                        {url}
                    </a>
                );
            },
        },
        {
            accessorKey: 'messages',
            header: 'Issue Type',
            cell: ({ getValue }) => {
                const messages = getValue() as string[];
                return (
                    <div className='text-sm max-w-sm'>
                        {messages[0] || 'No message'}
                    </div>
                );
            },
        },
        {
            accessorKey: 'tags',
            header: 'Tags',
            cell: ({ getValue }) => {
                const tags = getValue() as BlockerTag[];
                return (
                    <div className='flex flex-wrap gap-1'>
                        {tags.map(tag => (
                            <span 
                                key={tag.id}
                                className='inline-block bg-gray-200 rounded px-2 py-1 text-xs'
                            >
                                {tag.content}
                            </span>
                        ))}
                    </div>
                );
            },
        },
        {
            accessorKey: 'equalified',
            header: 'Status',
            cell: ({ getValue }) => {
                const equalified = getValue() as boolean;
                return (
                    <span className={`px-2 py-1 rounded text-xs ${
                        equalified ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'
                    }`}>
                        {equalified ? 'Fixed' : 'Active'}
                    </span>
                );
            },
        },
        /* {
            accessorKey: 'created_at',
            header: 'Date',
            cell: ({ getValue }) => {
                const date = getValue() as string;
                return <span className='text-sm whitespace-nowrap'>{formatDate(date)}</span>;
            },
        }, */
    ], []);

    const table = useReactTable({
        data: data?.blockers || [],
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        pageCount: data?.pagination?.totalPages || 0,
    });

    if (error) {
        return <div className='text-red-600'>Error loading blockers: {String(error)}</div>;
    }

    const hasFilters = selectedTags.length > 0 || selectedCategories.length > 0 || selectedStatus;
    const filterCount = selectedTags.length + selectedCategories.length + (selectedStatus ? 1 : 0);

    /* const handleTagToggle = (tagId: string) => {
        setSelectedTags(prev => 
            prev.includes(tagId) 
                ? prev.filter(id => id !== tagId)
                : [...prev, tagId]
        );
        setPage(0);
    }; */
    const handleTagToggle = (selected:MultiValue<Option>) => {
        setSelectedTags(selected as Option[]);
        setPage(0);
    }

    const handleCategoryToggle = (newVal:MultiValue<Option>) => {
        setSelectedCategories(newVal as Option[]);
        setPage(0)
    }
    /* 
    const handleTypeToggle = (type: string) => {
        setSelectedTypes(prev => 
            prev.includes(type) 
                ? prev.filter(t => t !== type)
                : [...prev, type]
        );
        setPage(0);
    }; */ 

    const handleStatusChange = (status: string) => {
        setSelectedStatus(status);
        setPage(0);
    };

    const clearAllFilters = () => {
        setSelectedTags([]);
        setSelectedCategories([]);
        setSelectedStatus('all');
        setPage(0);
    };

    return (
        <div className='mt-8'>
            <div className='flex flex-row items-center justify-between mb-4'>
                <h2>
                    All Blockers 
                    {hasFilters && ` (${filterCount} filter${filterCount !== 1 ? 's' : ''} active)`}
                </h2>
            </div>

            {/* Filter Controls */}
            <div className='mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200'>
                <div className='flex flex-col gap-4'>
                    {/* Status Filter */}
                    <div>
                        <label className='block text-sm font-semibold mb-2'>
                            Status:
                        </label>
                        <div className='flex gap-2'>
                            <ToggleGroup.Root
                                className="statusToggleGroup"
                                type="single"
                                defaultValue="all"
                                aria-label="View by status:"
                                value={selectedStatus}
                                onValueChange={handleStatusChange}
                            >
                                <ToggleGroup.Item
                                    value="active"
                                    aria-label="Active"
                                    className='data-[state=on]:bg-blue-500 data-[state=on]:text-white'
                                >
                                    Active
                                </ToggleGroup.Item>
                                <ToggleGroup.Item
                                    value="fixed"
                                    aria-label="Fixed"
                                    className='data-[state=on]:bg-blue-500 data-[state=on]:text-white'
                                >
                                    Fixed
                                </ToggleGroup.Item>
                                <ToggleGroup.Item
                                    value="all"
                                    aria-label="All"
                                    className='data-[state=on]:bg-blue-500 data-[state=on]:text-white'
                                >
                                    All
                                </ToggleGroup.Item>
                            </ToggleGroup.Root>{/* 
                            <button
                                onClick={() => handleStatusChange('')}
                                className={`px-3 py-1 rounded text-sm ${
                                    selectedStatus === '' 
                                        ? 'bg-blue-600 text-white' 
                                        : 'bg-white border border-gray-300 hover:bg-gray-100'
                                }`}
                                aria-pressed={selectedStatus === ''}
                            >
                                All
                            </button>
                            <button
                                onClick={() => handleStatusChange('active')}
                                className={`px-3 py-1 rounded text-sm ${
                                    selectedStatus === 'active' 
                                        ? 'bg-red-600 text-white' 
                                        : 'bg-white border border-gray-300 hover:bg-gray-100'
                                }`}
                                aria-pressed={selectedStatus === 'active'}
                            >
                                Active Only
                            </button>
                            <button
                                onClick={() => handleStatusChange('fixed')}
                                className={`px-3 py-1 rounded text-sm ${
                                    selectedStatus === 'fixed' 
                                        ? 'bg-green-600 text-white' 
                                        : 'bg-white border border-gray-300 hover:bg-gray-100'
                                }`}
                                aria-pressed={selectedStatus === 'fixed'}
                            >
                                Fixed Only
                            </button> */}
                        </div>
                    </div>

                    {/* Tag Filter */}
                    {availableTags && availableTags.length > 0 && (
                        <Select
                            options={availableTags}
                            isMulti
                            value={selectedTags}
                            placeholder='Filter by Tags...'
                            aria-label='Filter by Tags'
                            onChange={handleTagToggle} 
                        />
                    )}{/* 
                    {data?.availableTags && data.availableTags.length > 0 && (
                        <div>
                            <label className='block text-sm font-semibold mb-2'>
                                Tags ({selectedTags.length} selected):
                            </label>
                            <div className='flex flex-wrap gap-2'>
                                {data.availableTags.map((tag: BlockerTag) => (
                                    <button
                                        key={tag.id}
                                        onClick={() => handleTagToggle(tag.id)}
                                        className={`px-3 py-1 rounded text-sm ${
                                            selectedTags.includes(tag.id)
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-white border border-gray-300 hover:bg-gray-100'
                                        }`}
                                        aria-pressed={selectedTags.includes(tag.id)}
                                    >
                                        {tag.content}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
 */}
                    {/* Type Filter */}
                    {availableCategories && availableCategories.length > 0 && (
                        <Select 
                            options={availableCategories}
                            isMulti
                            value={selectedCategories}
                            placeholder='Filter by Categories...'
                            aria-label='Filter by Categories'
                            onChange={handleCategoryToggle} 
                        />
                    )}{/* 
                    {data?.availableCategories && data.availableCategories.length > 0 && (
                        <div>
                            <label className='block text-sm font-semibold mb-2'>
                                Categories ({selectedTypes.length} selected):
                            </label>
                            <div className='flex flex-wrap gap-2'>
                                {data.availableCategories.map((category: string) => (
                                    <button
                                        key={category}
                                        onClick={() => handleTypeToggle(category)}
                                        className={`px-3 py-1 rounded text-sm font-mono ${
                                            selectedTypes.includes(category)
                                                ? 'bg-purple-600 text-white'
                                                : 'bg-white border border-gray-300 hover:bg-gray-100'
                                        }`}
                                        aria-pressed={selectedTypes.includes(category)}
                                    >
                                        {category}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )} */}

                    {/* Clear Filters Button */}
                    {hasFilters && (
                        <div>
                            <button
                                onClick={clearAllFilters}
                                className='px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded text-sm font-semibold'
                            >
                                Clear All Filters
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {isLoading ? (
                <div className='text-center py-8'>Loading blockers...</div>
            ) : (
                <>
                    <div className='overflow-x-auto'>
                        <table className='w-full border-collapse border border-gray-300' aria-label='Blockers table'>
                            <thead>
                                {table.getHeaderGroups().map(headerGroup => (
                                    <tr key={headerGroup.id} className='bg-gray-100'>
                                        {headerGroup.headers.map(header => (
                                            <th
                                                key={header.id}
                                                scope='col'
                                                className='border border-gray-300 px-4 py-2 text-left font-semibold'
                                            >
                                                {header.isPlaceholder
                                                    ? null
                                                    : flexRender(
                                                        header.column.columnDef.header,
                                                        header.getContext()
                                                    )}
                                            </th>
                                        ))}
                                    </tr>
                                ))}
                            </thead>
                            <tbody>
                                {table.getRowModel().rows.length === 0 ? (
                                    <tr>
                                        <td 
                                            colSpan={columns.length} 
                                            className='border border-gray-300 px-4 py-8 text-center text-gray-500'
                                        >
                                            No blockers found
                                        </td>
                                    </tr>
                                ) : (
                                    table.getRowModel().rows.map((row, index) => (
                                        <tr 
                                            key={row.id}
                                            className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}
                                        >
                                            {row.getVisibleCells().map(cell => (
                                                <td
                                                    key={cell.id}
                                                    className='border border-gray-300 px-4 py-2'
                                                >
                                                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                                </td>
                                            ))}
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination Controls */}
                    <div className='mt-4 flex items-center justify-between' role='navigation' aria-label='Pagination'>
                        <div className='text-sm text-gray-600'>
                            Showing {data?.blockers?.length || 0} of {data?.pagination?.totalCount || 0} blockers
                            {data?.pagination && ` (Page ${page + 1} of ${data.pagination.totalPages})`}
                        </div>
                        <div className='flex gap-2'>
                            <button
                                onClick={() => setPage(0)}
                                disabled={page === 0}
                                className='px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed'
                                aria-label='Go to first page'
                            >
                                First
                            </button>
                            <button
                                onClick={() => setPage(p => Math.max(0, p - 1))}
                                disabled={page === 0}
                                className='px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed'
                                aria-label='Go to previous page'
                            >
                                Previous
                            </button>
                            <button
                                onClick={() => setPage(p => p + 1)}
                                disabled={!data?.pagination || page >= data.pagination.totalPages - 1}
                                className='px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed'
                                aria-label='Go to next page'
                            >
                                Next
                            </button>
                            <button
                                onClick={() => data?.pagination && setPage(data.pagination.totalPages - 1)}
                                disabled={!data?.pagination || page >= data.pagination.totalPages - 1}
                                className='px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed'
                                aria-label='Go to last page'
                            >
                                Last
                            </button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
};
