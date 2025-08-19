import { useQuery } from '@tanstack/react-query';
import { formatDate, formatId } from '../utils';
import * as API from 'aws-amplify/api';
import { Link, useNavigate, useParams } from 'react-router-dom';
const apiClient = API.generateClient();
import Editor from '@monaco-editor/react';

export const Audit = () => {
    const navigate = useNavigate();
    const { auditId } = useParams();
    const { data: audit } = useQuery({
        queryKey: ['audit', auditId],
        queryFn: async () => {
            const results = await (await API.get({
                apiName: 'auth', path: '/getAuditResults', options: { queryParams: { id: auditId, type: 'json' } }
            }).response).body.json();
            return results;
        },
    });
    return <div>
        <div className='flex flex-col'>
            <Link to={-1}>← Go Back</Link>
            <h1>Audit</h1>
        </div>
        {audit && <Editor
            height="600px"
            defaultLanguage="json"
            defaultValue={JSON.stringify(audit)}
            onMount={(editor) => { editor.getAction('editor.action.formatDocument').run(); }}
        />}
    </div>
}