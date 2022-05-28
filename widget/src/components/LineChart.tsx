import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
// import zoomPlugin from 'chartjs-plugin-zoom';
import { Line } from 'react-chartjs-2';
import { data } from '../data';
import { formatDate } from '../utils';


ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  // zoomPlugin
);


// const renewable = data.filter(i => i.type === 'RENEWABLE')
// const total = data.filter(i => i.type === 'TOTAL')

// export const dataPrepared = {
//   labels: renewable.map(i => i.year),
//   datasets: [
//     {
//       label: 'Renewable',
//       data: renewable.map(i => i.amount),
//       borderColor: 'rgb(255, 99, 132)',
//       backgroundColor: 'rgba(255, 99, 132, 0.5)',
//       // tension: 0.1
//     },
//     {
//       label: 'Total',
//       data: total.map(i => i.amount),
//       borderColor: 'rgb(53, 162, 235)',
//       backgroundColor: 'rgba(53, 162, 235, 0.5)',
//       // tension: 0.1
//     },
//   ],
// };

interface Props {
    data: any
    range: any
}

export default  function LineChart ({ data, range }: Props) {
    data = data.sort((a: any, b: any) => new Date(a.dimensions.ts).getTime() - new Date(b.dimensions.ts).getTime())

    const dataPrepared = {
        labels: data.map((i: any) => formatDate(i.dimensions.ts, range.days === 1)),
        datasets: [
            {
                label: 'Visits',
                data: data.map((i: any) => i.sum.visits),
                borderColor: 'rgb(53, 162, 235)',
                backgroundColor: 'rgba(53, 162, 235, 0.5)',
            },
        ],
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
              display: false
          },
          title: {
            display: true,
            text: `Visits - ${range.title}`,
          },
          // zoom: {
          //   zoom: {
          //     wheel: {
          //       enabled: true,
          //     },
          //     pinch: {
          //       enabled: true
          //     },
          //     mode: 'xy',
          //   }
          // }
        },
      };

    return (
        <div style={{ height: 200 }}>
            <Line options={options} data={dataPrepared} />
        </div>
    )
}
