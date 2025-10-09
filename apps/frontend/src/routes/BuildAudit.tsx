import { useState, MouseEvent, FormEvent, KeyboardEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useUser } from '../queries';
import * as API from 'aws-amplify/api';

interface Page {
    url: string;
    type: 'html' | 'pdf';
}

export const BuildAudit = () => {
    const [importBy, setImportBy] = useState('URLs');
    const [emailNotifications, setEmailNotifications] = useState(false);
    const [pages, setPages] = useState<Page[]>([]);
    const [urlError, setUrlError] = useState<string | null>(null);

    const { data: user } = useUser();
    const navigate = useNavigate();

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

    const addPage = (e: MouseEvent) => {
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
        setPages([...pages, { url: validUrl, type: 'html' }])
        // Clear the input field
        const inputField = form.querySelector('[name="pageInput"]') as HTMLInputElement;
        if (inputField) inputField.value = '';
        setUrlError(null);
        return;
    }

    const removePage = (e: MouseEvent) => {
        e.preventDefault();
        const button = e.currentTarget;
        const form = button.closest('form');
        if (!form) return;

        const checkboxes = form.querySelectorAll('[name="pageCheckbox"]:checked');
        const toRemove = Array.from(checkboxes).map(cb => (cb as HTMLInputElement).value);
        setPages(pages.filter(row => !toRemove.includes(row.url)));
        return;
    }

    const updatePageType = (url: string, type: 'html' | 'pdf') => {
        setPages(pages.map(page =>
            page.url === url ? { ...page, type } : page
        ));
    }

    const handleUrlInputKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
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
            setPages([...pages, { url: validUrl, type: 'html' }])
            // Clear the input field
            input.value = '';
            setUrlError(null);
        }
    }

    const buildAuditData = (formData: FormData) => {
        return {
            auditName: formData.get('auditName') as string,
            scanFrequency: formData.get('scanFrequency') as string,
            emailNotifications: emailNotifications,
            emailFrequency: emailNotifications ? formData.get('emailFrequency') as string : null,
            pages: pages.map(page => ({
                url: page.url,
                type: page.type
            }))
        };
    }

    const saveAndRunAudit = async (e: FormEvent) => {
        e.preventDefault();
        if (pages.length === 0) {
            window.alert(`You need to add at least 1 page to your audit.`);
            return;
        }
        const formData = new FormData(e.currentTarget as HTMLFormElement);
        const auditData = buildAuditData(formData);
        console.log('Audit Data (Save & Run):', JSON.stringify(auditData));
        const response = await (await API.post({
            apiName: 'auth', path: '/saveAudit', options: { body: { ...auditData, saveAndRun: true } }
        }).response).body.json();
        navigate(`/audits/${response?.id}`);
        return;
    }

    const saveAudit = async (e: FormEvent) => {
        e.preventDefault();
        if (pages.length === 0) {
            window.alert(`You need to add at least 1 page to your audit.`);
            return;
        }
        const form = (e.currentTarget as HTMLElement).closest('form');
        if (!form) return;
        const formData = new FormData(form);
        const auditData = buildAuditData(formData);
        console.log('Audit Data (Save):', JSON.stringify(auditData));
        console.log('Audit Data (Save & Run):', JSON.stringify(auditData));
        const response = await (await API.post({
            apiName: 'auth', path: '/saveAudit', options: { body: { ...auditData, saveAndRun: false } }
        }).response).body.json();
        navigate(`/audits/${response?.id}`);
        return;
    }

    return <div className='flex flex-col gap-4 max-w-screen-sm'>
        <Link to="..">← Go Back</Link>
        <h1>Audit Builder</h1>
        <form className='flex flex-col gap-4' onSubmit={saveAndRunAudit}>
            <h2>General Info</h2>
            <div className='flex flex-col'>
                <label htmlFor='auditName'>Audit Name:</label>
                <input id='auditName' name='auditName' />
            </div>
            <div className='flex flex-col'>
                <label htmlFor='scanFrequency'>Scan Frequency:</label>
                <select id='scanFrequency' name='scanFrequency'>
                    <option>Manually</option>
                    <option>Daily</option>
                    <option>Weekly</option>
                    <option>Monthly</option>
                    <option>On Monitor Update</option>
                </select>
            </div>
            <div className='flex flex-col'>
                <label htmlFor='emailNotifications'>Email Notifications:</label>
                <div>
                    <input type='checkbox' id='emailNotifications' name='emailNotifications' checked={emailNotifications} onChange={(e) => setEmailNotifications(e.target.checked)} />
                    <label htmlFor='emailNotifications'>Email summary to {user?.name}, {user?.email}.</label>
                </div>
            </div>
            {emailNotifications && <div className='flex flex-col'>
                <label htmlFor='emailFrequency'>Email Frequency:</label>
                <select id='emailFrequency' name='emailFrequency'>
                    <option>Daily</option>
                    <option>Weekly</option>
                    <option>Monthly</option>
                </select>
            </div>}

            <h2>Add URLs</h2>
            <div className='flex flex-col'>
                <label htmlFor='importBy'>Import By:</label>
                <select id='importBy' name='importBy' value={importBy} onChange={(e) => setImportBy(e.target.value)}>
                    <option>URLs</option>
                    <option>CSV</option>
                </select>
            </div>
            {['URLs'].includes(importBy) && <div className='flex flex-col'>
                <label htmlFor='pageInput'>URLs:</label>
                <input
                    id='pageInput'
                    name='pageInput'
                    onKeyDown={handleUrlInputKeyDown}
                    placeholder='example.com'
                />
                {urlError && <p className='text-red-500 text-sm mt-1'>{urlError}</p>}
            </div>}
            {['CSV'].includes(importBy) && <div className='flex flex-col'>
                <label htmlFor='pageInput'>CSV Upload:</label>
                <input id='pageInput' name='pageInput' type='file' />
            </div>}
            <button type='button' onClick={addPage}>Add Pages</button>

            {pages.length > 0 && <>
                <h2>Review Added Pages</h2>
                {pages.map(page => <div key={page.url}>
                    <input id={page.url} name='pageCheckbox' type='checkbox' value={page.url} defaultChecked={true} />
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
            </>}

            <div className='border-[1px] border-border' />
            <div className='flex flex-row gap-2'>
                <button type='button' onClick={saveAudit} className='w-full'>Save Audit</button>
                <button type='submit' className='w-full'>Save & Run Audit</button>
            </div>
        </form>
    </div>
}