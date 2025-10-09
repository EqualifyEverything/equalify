import { useQuery } from '@tanstack/react-query';
import { formatDate, formatId } from '../utils';
import * as API from 'aws-amplify/api';
import { Link, useParams } from 'react-router-dom';
const apiClient = API.generateClient();
import Editor from '@monaco-editor/react';
import { useRef, useEffect } from 'react';
import type { editor } from 'monaco-editor';

export const Audit = () => {
    const { auditId } = useParams();
    const editorRef = useRef<editor.IStandaloneCodeEditor | null>(null);
    
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

    return <div>
        <div className='flex flex-col'>
            <Link to={'/audits'}>← Go Back</Link>
            <h1>Audit</h1>
        </div>
        {audit && <Editor
            height="600px"
            defaultLanguage="json"
            value={JSON.stringify(audit, null, 2)}
            onMount={(editor) => {
                editorRef.current = editor;
                editor.getAction('editor.action.formatDocument')?.run();
            }}
        />}
    </div>
}