import { useState } from 'react';
import cn from 'classnames'

import Grid from '@mui/material/Grid';
import Box from '@mui/material/Box';
import Card from '@mui/material/Card';
import CardContent from '@mui/material/CardContent';
import Typography from '@mui/material/Typography';

import List from '@mui/material/List';
import Button from '@mui/material/Button';

import LinearProgress from '@mui/material/LinearProgress';
import styles from './ProgressList.module.scss'
import { formatNumber } from '../utils';


interface Props {
    title: string
    list: any
    totalVisits: number
}

export default function ProgressList ({ title, list, totalVisits }: Props) {
    const [collapsed, setCollapsed] = useState(true)

    return (
        <Grid item xs={12} sm={6}>
            <Card variant="outlined">
                <CardContent className={cn(styles.card, { [styles.noBottomPadding]: list?.length !== 3 })}>
                    <Typography sx={{ fontSize: 14 }} color="text.secondary" gutterBottom>
                        {title}
                    </Typography>
                    <List dense className={cn(styles.list, { [styles.collapsed]: collapsed })}>
                        {
                            list?.map((item: any, i: number) => {
                                const percents = Math.round((item.sum.visits/totalVisits * 100) * 100) / 100
                                let name = item.dimensions.metric

                                if (title === 'Countries') {
                                    name = new Intl.DisplayNames(['en'], { type: 'region' }).of(name)
                                }

                                return (
                                    <Box
                                        sx={{ position: 'relative', display: 'flex', alignItems: 'center', justifyContent: 'space-between', my: 1, overflow: 'hidden', textOverflow: 'ellipsis' }}
                                        title={`${name} - ${item.sum.visits} visits (${percents}%)`}
                                        key={i}
                                    >
                                        <Box sx={{ minWidth: 35, mr: 1 }}>
                                            <Typography className={styles.small} variant="body2" color="text.primary" noWrap>{name}</Typography>
                                        </Box>
                                        <Box sx={{ display: 'flex', alignItems: 'center' }}>
                                            <Box sx={{ minWidth: 35, mr: 1 }}>
                                                <Typography className={styles.small} variant="body2" color="text.secondary" align="right">{formatNumber(item.sum.visits)}</Typography>
                                            </Box>
                                            <Box sx={{ width: '25px' }}>
                                                <LinearProgress variant="determinate" value={percents} />
                                            </Box>
                                        </Box>
                                        {
                                            title === 'Paths' &&
                                            <a className={styles.boxLink} href={(window.cwaSettings?.frontendDomain ? `//${window.cwaSettings?.frontendDomain}` : '') + name} target="_blank" rel="noreferrer"> </a>
                                        }
                                    </Box>
                                )
                            })
                        }
                    </List>
                    {   list?.length > 3 &&
                        <Button size="small" fullWidth onClick={() => setCollapsed(!collapsed)}>{collapsed ? 'More' : 'Less'}</Button>
                    }
                </CardContent>
            </Card>
        </Grid>

    )
}
