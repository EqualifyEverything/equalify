import { useGlobalStore } from '../utils';

export const Dashboard = () => {
    const { authenticated } = useGlobalStore();

    return (<div className='flex flex-col w-full'>
        Dashboard
    </div>)
};
