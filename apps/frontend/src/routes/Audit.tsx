import { useQuery, useQueryClient } from '@tanstack/react-query';
import { formatDate, formatId } from '../utils';
import * as API from 'aws-amplify/api';
import { Link, useNavigate, useParams } from 'react-router-dom';
const apiClient = API.generateClient();
import Editor from '@monaco-editor/react';
import { useRef, useEffect, useState } from 'react';
import type { editor } from 'monaco-editor';

interface Page {
    url: string;
    type: 'html' | 'pdf';
}

export const Audit = () => {
    const { auditId } = useParams();
    const editorRef = useRef<editor.IStandaloneCodeEditor | null>(null);
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const [pages, setPages] = useState<Page[]>([]);
    const [urlError, setUrlError] = useState<string | null>(null);

    const { data: urls } = useQuery({
        queryKey: ['urls', auditId],
        queryFn: async () => (await apiClient.graphql({
            query: `query($audit_id: uuid){urls(where:{audit_id:{_eq:$audit_id}},order_by: {created_at: desc}) {id url type}}`,
            variables: { audit_id: auditId }
        }))?.data?.urls,
        initialData: [],
    });

    useEffect(() => {
        console.log(urls)
        setPages(urls)
    }, [urls]);

    const { data: audit } = useQuery({
        queryKey: ['audit', auditId],
        queryFn: async () => {
            const results = await (await API.get({
                apiName: 'auth', path: '/getAuditResults', options: { queryParams: { id: auditId!, type: 'json' } }
            }).response).body.json();
            return results;
        },
        refetchInterval: 5000,
    });

    // Format the JSON whenever audit data changes
    useEffect(() => {
        if (editorRef.current && audit) {
            setTimeout(() => {
                editorRef.current?.getAction('editor.action.formatDocument')?.run();
            }, 100);
        }
    }, [audit]);

    const renameAudit = async () => {
        const newName = prompt(`What would you like to rename this audit to?`, audit?.name)
        if (newName) {
            const response = await (await API.post({
                apiName: 'auth', path: '/updateAudit', options: { body: { id: auditId!, name: newName } }
            }).response).body.json();
            console.log(response);
            await queryClient.refetchQueries({ queryKey: ['audit', auditId] });
            return;
        }
    }

    const deleteAudit = async () => {
        if (confirm(`Are you sure you want to delete this audit?`)) {
            const response = await (await API.post({
                apiName: 'auth', path: '/deleteAudit', options: { body: { id: auditId! } }
            }).response).body.json();
            console.log(response);
            await queryClient.refetchQueries({ queryKey: ['audits'] });
            navigate('/audits');
            return;
        }
    }

    const validateAndFormatUrl = (input: string): string | null => {
        // Trim whitespace
        let url = input.trim();
        if (!url) return null;

        // Add https:// if no protocol is specified
        if (!url.match(/^https?:\/\//i)) {
            url = 'https://' + url;
        }

        // Validate URL format
        try {
            const urlObj = new URL(url);
            // Check if it's http or https
            if (!['http:', 'https:'].includes(urlObj.protocol)) {
                setUrlError('Only HTTP and HTTPS URLs are supported');
                return null;
            }
            setUrlError(null);
            return urlObj.href;
        } catch {
            setUrlError('Invalid URL format. Please enter a valid URL (e.g., example.com or https://example.com)');
            return null;
        }
    }

    const addPage = async (e: MouseEvent) => {
        e.preventDefault();
        const button = e.currentTarget;
        const form = button.closest('form');
        if (!form) return;

        const formData = new FormData(form);
        const input = formData.get('pageInput') as string;
        if (!input || input.length === 0) {
            return;
        }

        // Validate and format URL
        const validUrl = validateAndFormatUrl(input);
        if (!validUrl) return;

        // Check for duplicates
        if (pages.some(page => page.url === validUrl)) {
            setUrlError('This URL has already been added');
            return;
        }
        // Add page with default type of 'html'
        await apiClient.graphql({
            query: `mutation ($audit_id: uuid, $url: String, $type: String) {
                insert_urls_one(object: {audit_id: $audit_id, url: $url, type: $type}) {id}
            }`,
            variables: {
                audit_id: auditId,
                url: validUrl,
                type: 'html',
            }
        })
        await apiClient.graphql({
            query: `mutation ($audit_id: uuid, $message: String, $data: jsonb) {
                insert_logs_one(object: {audit_id: $audit_id, message: $message, data: $data}) {id}
            }`,
            variables: {
                audit_id: auditId,
                message: `User added ${validUrl}`,
                data: { url: validUrl, type: 'html' },
            }
        })
        await queryClient.refetchQueries({ queryKey: ['urls', auditId] })
        // Clear the input field
        const inputField = form.querySelector('[name="pageInput"]') as HTMLInputElement;
        if (inputField) inputField.value = '';
        setUrlError(null);
        return;
    }

    const removePage = async (e: MouseEvent) => {
        e.preventDefault();
        const button = e.currentTarget;
        const form = button.closest('form');
        if (!form) return;

        const checkboxes = form.querySelectorAll('[name="pageCheckbox"]:checked');
        const toRemove = Array.from(checkboxes).map(cb => (cb as HTMLInputElement).value);
        // setPages(pages.filter(row => !toRemove.includes(row.url)));
        for (const row of toRemove) {
            await apiClient.graphql({
                query: `mutation($audit_id:uuid,$url:String) {delete_urls(where: {audit_id: {_eq: $audit_id}, url: {_eq: $url}}) {affected_rows}}`,
                variables: {
                    audit_id: auditId,
                    url: row,
                }
            })

            await apiClient.graphql({
                query: `mutation ($audit_id: uuid, $message: String, $data: jsonb) {
                insert_logs_one(object: {audit_id: $audit_id, message: $message, data: $data}) {id}
            }`,
                variables: {
                    audit_id: auditId,
                    message: `User removed ${row}`,
                    data: { url: row, type: 'html' },
                }
            })
        }
        await queryClient.refetchQueries({ queryKey: ['urls', auditId] })
        return;
    }

    const updatePageType = (url: string, type: 'html' | 'pdf') => {
        setPages(pages.map(page =>
            page.url === url ? { ...page, type } : page
        ));
    }

    const handleUrlInputKeyDown = async (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const input = e.currentTarget;
            const form = input.closest('form');
            if (!form) return;

            const inputValue = input.value;
            if (!inputValue || inputValue.length === 0) {
                return;
            }

            // Validate and format URL
            const validUrl = validateAndFormatUrl(inputValue);
            if (!validUrl) return;

            // Check for duplicates
            if (pages.some(page => page.url === validUrl)) {
                setUrlError('This URL has already been added');
                return;
            }

            // Add page with default type of 'html'

            // Add page with default type of 'html'
            await apiClient.graphql({
                query: `mutation ($audit_id: uuid, $url: String, $type: String) {
                insert_urls_one(object: {audit_id: $audit_id, url: $url, type: $type}) {id}
            }`,
                variables: {
                    audit_id: auditId,
                    url: validUrl,
                    type: 'html',
                }
            })

            await apiClient.graphql({
                query: `mutation ($audit_id: uuid, $message: String, $data: jsonb) {
                insert_logs_one(object: {audit_id: $audit_id, message: $message, data: $data}) {id}
            }`,
                variables: {
                    audit_id: auditId,
                    message: `User added ${validUrl}`,
                    data: { url: validUrl, type: 'html' },
                }
            })
            await queryClient.refetchQueries({ queryKey: ['urls', auditId] })
            // Clear the input field
            input.value = '';
            setUrlError(null);
        }
    }

    return <div className='max-w-screen-sm'>
        <div className='flex flex-col gap-2'>
            <Link to={'/audits'}>← Go Back</Link>
            <div className='flex flex-row items-center gap-2 justify-between'>
                <h1>Audit: {audit?.name}</h1>
                <div className='flex flex-row items-center gap-2'>
                    <button onClick={renameAudit}>Rename</button>
                    <button onClick={deleteAudit}>Delete</button>
                </div>
            </div>
        </div>
        <form onSubmit={addPage}>
            <div className='flex flex-col'>
                <label htmlFor='pageInput'>URLs:</label>
                <input
                    id='pageInput'
                    name='pageInput'
                    onKeyDown={handleUrlInputKeyDown}
                    placeholder='example.com'
                />
                {urlError && <p className='text-red-500 text-sm mt-1'>{urlError}</p>}
            </div>
            <button type='button' onClick={addPage}>Add Pages</button>
            <h2>Review Added Pages</h2>
            {urls.map(page => <div key={page.url}>
                <input id={page.url} name='pageCheckbox' type='checkbox' value={page.url} />
                <label htmlFor={page.url}>{page.url}</label>
                <select
                    name={`pageType_${page.url}`}
                    value={page.type}
                    onChange={(e) => updatePageType(page.url, e.target.value as 'html' | 'pdf')}
                    className='!p-0 mx-1'
                >
                    <option value='html'>HTML</option>
                    <option value='pdf'>PDF</option>
                </select>
            </div>)}
            <button type='button' onClick={removePage}>Remove Pages</button>
        </form>
        {audit && <Editor
            className='mt-2'
            height="500px"
            defaultLanguage="json"
            value={JSON.stringify(audit, null, 2)}
            onMount={(editor) => {
                editorRef.current = editor;
                editor.getAction('editor.action.formatDocument')?.run();
            }}
        />}
    </div>
}