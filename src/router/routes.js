import React from 'react';
import Dashboard      from '../page/dashboard/dashboard.jsx';
import Settings       from '../page/settings/settings.jsx';
import Integrations   from '../page/integrations/integrations.jsx';
import Templates      from '../page/templates/templates.jsx';
import Funnels        from '../page/funnels/funnels.jsx';
import Logs           from '../page/logs/logs.jsx';
import DocsCredentials from '../page/docs/docs-credentials.jsx';

const routes = [
    { path: '/',                    element: <Dashboard />        },
    { path: '/settings',            element: <Settings />         },
    { path: '/integrations',        element: <Integrations />     },
    { path: '/templates',           element: <Templates />        },
    { path: '/funnels',             element: <Funnels />          },
    { path: '/logs',                element: <Logs />             },
    { path: '/docs/credentials',    element: <DocsCredentials />  },
];

export default routes;
