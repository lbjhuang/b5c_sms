/**
 * 日志详情模板页面
 */
const LogComponent = {
    name: 'LogComponent',
    data() {
        return {

        }
    },
    props: {
        logs: Array, //
    },
    methods: {
        getRowClass({ row, column, rowIndex, columnIndex }) {
            if (rowIndex === 0) {
                return 'background: #546e7a;color: #fff;'
            }
        },
    },
    template: `
    <el-table :data="logs" border align="center" :header-cell-style="getRowClass">
        <el-table-column :label="$lang('操作日志')">
            <el-table-column prop="created_at" :label="$lang('操作时间')"></el-table-column>
            <el-table-column prop="created_by" :label="$lang('操作人')"></el-table-column>
            <el-table-column prop="content" :label="$lang('详细信息')"></el-table-column>
        </el-table-column>
    </el-table>
    `
}

const pageTemplage = `
<div slot="footer" class="dialog-footer">
    <div class="col-100 text-right">
        <el-pagination background @size-change="handleLogSize" @current-change="handleLogCurrent" layout="sizes, prev, pager, next" :page-sizes="[10, 50, 100]"
            :total="logTotal">
        </el-pagination>
    </div>
</div>
`