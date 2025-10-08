import { useQuery } from '@tanstack/react-query';
import { formatDate, formatId } from '../utils';
import * as API from 'aws-amplify/api';
import { Link, useNavigate } from 'react-router-dom';
const apiClient = API.generateClient();

export const Audits = () => {
    const navigate = useNavigate();
    const { data: audits } = useQuery({
        queryKey: ['audits'],
        queryFn: async () => (await apiClient.graphql({
            query: `{audits {id created_at name}}`,
        }))?.data?.audits,
    });
    return <div>
        <div className='flex flex-row items-center justify-start gap-4'>
            <h1>Audits</h1>
            <button onClick={() => navigate('/audits/build')}>+ Add Audit</button>
        </div>
        {audits?.map(row => <div className='flex flex-row items-center gap-2 py-4'>
            <Link className='hover:opacity-50' to={`/audits/${formatId(row.id)}`}>{row.name}</Link>
            <div className='opacity-50'>{formatDate(row.created_at)}</div>
        </div>)}
    </div>
}