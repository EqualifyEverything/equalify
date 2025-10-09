import { useQuery, useQueryClient } from '@tanstack/react-query';
import { formatDate, formatId } from '../utils';
import * as API from 'aws-amplify/api';
import { Link, useNavigate, useParams } from 'react-router-dom';
const apiClient = API.generateClient();
import Editor from '@monaco-editor/react';
import { useRef, useEffect } from 'react';
import type { editor } from 'monaco-editor';

export const Audit = () => {
    const { auditId } = useParams();
    const editorRef = useRef<editor.IStandaloneCodeEditor | null>(null);
    const queryClient = useQueryClient();
    const navigate = useNavigate();

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

    return <div className='max-w-screen-sm'>
        <div className='flex flex-col gap-2'>
            <Link to={'/audits'}>← Go Back</Link>
            <div className='flex flex-row items-center gap-2 justify-between'>
                <h1>Audit: {audit?.name}</h1>
                <button onClick={deleteAudit}>Delete</button>
            </div>
        </div>
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