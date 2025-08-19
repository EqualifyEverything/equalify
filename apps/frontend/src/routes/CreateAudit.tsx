import { useState } from 'react'
import { Link } from 'react-router-dom'

export const CreateAudit = () => {
    const [importBy, setImportBy] = useState('Page URLs');
    const [pages, setPages] = useState([]);

    const addPage = (e) => {
        e.preventDefault();
        const { input } = Object.fromEntries(new FormData(e.currentTarget));
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

    return <div className='flex flex-col gap-4'>
        <Link to={-1}>← Go Back</Link>
        <h1>Audit Builder - Step 1</h1>
        <div className='h-[550px] overflow-y-scroll'>
            <form onSubmit={addPage} className='flex flex-col max-w-[300px] gap-4'>
                <h2>Add Pages</h2>
                <div className='flex flex-col'>
                    <label>Import By:</label>
                    <select id='importBy' name='importBy' value={importBy} onChange={(e) => setImportBy(e.target.value)}>
                        <option>Page URLs</option>
                        <option>Sitemap</option>
                        <option>CSV</option>
                    </select>
                </div>
                {['Page URLs', 'Sitemap'].includes(importBy) && <div className='flex flex-col'>
                    <label htmlFor='input'>Page URLs:</label>
                    <input id='input' name='input' />
                </div>}
                {['CSV'].includes(importBy) && <div className='flex flex-col'>
                    <label htmlFor='input'>CSV Upload:</label>
                    <input id='input' name='input' type='file' />
                </div>}
                <button>Add Pages</button>
            </form>
            {pages.length > 0 && <form onSubmit={removePage} className='flex flex-col max-w-[300px] gap-4'>
                <h2>Review Added Pages</h2>
                {pages.map(row => <div>
                    <input id={row} name='input' type='checkbox' value={row} />
                    <label htmlFor={row}>{row}</label>
                </div>)}
                <button>Remove Pages</button>
            </form>}
        </div>
        <button>Next Step</button>
    </div>
}