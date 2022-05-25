import { useCallback, useEffect, useState } from 'react';

import Grid from '@mui/material/Grid';
import Box from '@mui/material/Box';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';
import LinearProgress from '@mui/material/LinearProgress';

import MenuItem from '@mui/material/MenuItem';
import FormControl from '@mui/material/FormControl';
import Select from '@mui/material/Select';

import './App.css';

import LineChart from './components/LineChart';
import ProgressList from './components/ProgressList';
import { formatNumber } from './utils';
// https://mui-plus.vercel.app/components/Sparkline

declare global {
  interface Window {
    cwaSettings: CwaSettings
    wpApiSettings: WpApiSettings
  }
}

interface CwaSettings {
  slug: string;
  frontendDomain: string
}

interface WpApiSettings {
    root: string;
    nonce: string;
  }

const RANGE_LIST = [
    {
        days: 1,
        title: 'Previous 24 hours'
    },
    {
        days: 7,
        title: 'Previous 7 days'
    },
    {
        days: 30,
        title: 'Previous 30 days'
    }
]

function App() {
    const { cwaSettings, wpApiSettings } = window
    const [stats, setStats] = useState<any>(null)
    const [range, setRange] = useState({
        days: 30,
        title: 'Previous 30 days',
        from: getDate(),
        to: getDate(30)
    })
    const [isLoading, setIsLoading] = useState(true)

    const totalVisits = stats?.total[0]?.sum?.visits || 0

    const getData = useCallback( async ( endpoint: string, from: string, to: string, limit: number = 15 ) => {
        setIsLoading(true)
        const headers = new Headers()
        headers.set('X-WP-Nonce', wpApiSettings?.nonce);

        const slug = cwaSettings?.slug ? `&slug=${cwaSettings?.slug}` : ''

        let response = await fetch(`${wpApiSettings?.root}${endpoint}?from=${from}&to=${to}&limit=${limit}${slug}`, {
          headers
        }).catch(() => setIsLoading(false))
        // const response = await fetch('/mock.json')
        const stats = await response?.json()
        console.log(stats)

        setStats(stats)
        setIsLoading(false)
    }, [setStats, cwaSettings, wpApiSettings, setIsLoading])

    useEffect(() => {
        getData('cwa/v1/stats', range?.from, range?.to)
    }, [getData, range])

    function handleSetRange (days: number) {
        let range = RANGE_LIST.find(i => i.days === days)

        if (range) {
            const from = getDate()
            const to = getDate(range.days)

            setRange({
                ...range,
                from,
                to
            })

            getData('cwa/v1/stats', from, to)
        }
    }

    function getDate (days: number = 0) {
        const d = new Date();

        if (days) {
            d.setDate(d.getDate() - days);
        }

        return d.toJSON()
    }

  return (
    <div className="App">
        <Grid container spacing={1} mb={1} justifyContent="flex-end">
            <Grid item >
            <Box sx={{ minWidth: 180 }}>
                <FormControl fullWidth  size="small">
                    <Select
                        id="demo-simple-select"
                        value={range.days}
                        onChange={(event) => {
                            handleSetRange(parseInt(`${event.target.value}`));
                        }}
                    >
                        {
                            RANGE_LIST.map(({ days, title }) => (
                                <MenuItem value={days} key={days}>{title}</MenuItem>
                            ))
                        }
                    </Select>
                </FormControl>
            </Box>
            </Grid>
        </Grid>
        {
            isLoading && <LinearProgress />
        }
        {
            stats?.total?.length
            ?   <>
                    <Grid container spacing={1}>
                        <Grid item xs={6}>
                            <Card variant="outlined" title={`Visits - ${totalVisits}`}>
                                <CardContent>
                                    <Typography sx={{ fontSize: 14 }} color="text.secondary" gutterBottom>
                                        Visits
                                    </Typography>
                                    <Typography variant="h5" component="div">
                                        {formatNumber(totalVisits)}
                                    </Typography>
                                </CardContent>
                            </Card>
                        </Grid>

                        <Grid item xs={6}>
                            <Card variant="outlined" title={`Page views - ${stats.total[0].count}`}>
                                <CardContent>
                                    <Typography sx={{ fontSize: 14 }} color="text.secondary" gutterBottom>
                                        Page views
                                    </Typography>
                                    <Typography variant="h5" component="div">
                                        {formatNumber(stats.total[0].count)}
                                    </Typography>
                                </CardContent>
                            </Card>
                        </Grid>
                    </Grid>
                    <LineChart data={stats?.visits} range={range}/>
                    <Grid container spacing={1} mt={1}>
                        {
                            !cwaSettings?.slug &&
                            <ProgressList title="Paths" list={stats?.topPaths} totalVisits={totalVisits}/>
                        }
                        <ProgressList title="Countries" list={stats?.countries} totalVisits={totalVisits}/>
                        <ProgressList title="Browsers" list={stats?.topBrowsers} totalVisits={totalVisits}/>
                        <ProgressList title="Device Types" list={stats?.topDeviceTypes} totalVisits={totalVisits}/>
                        <ProgressList title="Operation Systems" list={stats?.topOSs} totalVisits={totalVisits}/>
                        <ProgressList title="Referrers" list={stats?.topReferrers} totalVisits={totalVisits}/>
                    </Grid>
                </>
            :   !isLoading ? <Typography sx={{ fontSize: 14 }} color="text.secondary">No data</Typography>: null
        }
    </div>
  );
}

export default App;
