import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useUser } from '../queries';

export const CreateAudit = () => {
    const [importBy, setImportBy] = useState('URLs');
    const [emailNotifications, setEmailNotifications] = useState(false);
    const [pages, setPages] = useState([]);

    const { data: user } = useUser();

    const addPage = (e) => {
        e.preventDefault();
        const { input } = Object.fromEntries(new FormData(e.currentTarget));
        if (input?.length === 0) {
            return;
        }
        setPages([...pages, input])
        e.target.reset();
        return;
    }

    const removePage = (e) => {
        e.preventDefault();
        const { input } = Object.fromEntries(new FormData(e.currentTarget));
        setPages(pages.filter(row => row !== input))
        return;
    }

    return <div className='flex flex-col gap-4 max-w-screen-sm'>
        <Link to={-1}>← Go Back</Link>
        <h1>Audit Builder</h1>
        <div className=''>
            <form onSubmit={addPage} className='flex flex-col gap-4'>
                <h2>General Info</h2>
                <div className='flex flex-col'>
                    <label htmlFor='input'>Audit Name:</label>
                    <input id='input' name='input' />
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
                        <input type='checkbox' id='emailNotifications' name='emailNotifications' value={emailNotifications} onChange={(e) => setEmailNotifications(e.target.checked)} />
                        <span>Email summary to {user?.name}, {user?.email}.</span>
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
                    <label htmlFor='input'>URLs:</label>
                    <input id='input' name='input' />
                </div>}
                {['CSV'].includes(importBy) && <div className='flex flex-col'>
                    <label htmlFor='input'>CSV Upload:</label>
                    <input id='input' name='input' type='file' />
                </div>}
                <button>Add Pages</button>
            </form>
            {pages.length > 0 && <form onSubmit={removePage} className='flex flex-col gap-4'>
                <h2>Review Added Pages</h2>
                {pages.map(row => <div>
                    <input id={row} name='input' type='checkbox' value={row} />
                    <label htmlFor={row}>{row}</label>
                </div>)}
                <button>Remove Pages</button>
            </form>}
        </div>
        <div className='border-[1px] border-border' />
        <div className='flex flex-row gap-2'>
            <button className='w-full'>Save Audit</button>
            <button className='w-full'>Save & Run Audit</button>
        </div>
    </div>
}