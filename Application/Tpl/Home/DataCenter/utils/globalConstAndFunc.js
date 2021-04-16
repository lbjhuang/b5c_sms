/** 
 * ！！！！！！！！！
 * ！！！请勿直接或间接的修改此文件中常量和函数的值
 * ！！！该文件涉及多个页面
 * ！！！如需修改，请提前咨询——墨尘
 * ！！！！！！！！！
 * 该文件用以存储ERP数据看板模块下， 经常使用到的一些通用常量和函数
 */

var GlobalConstAndFunc = Object.freeze({
  /**
   *  ERP UI规范 部分
   */
  UI: {
    // 主色
    primaryColor: '#0375DE',
    // 数据色值
    colors2To8: ['#0375DE', '#13C2C2', '#FFC30F', '#FA8C16', '#F14E22', '#BF2741', '#8D34A7', '#03315B',],
    colors9To16: ['#0375DE', '#2889E2', '#13C2C2', '#36CBCB', '#FFC30F', '#FFCB32', '#FA8C16', '#FA9D38', '#F14E22', '#F36842', '#BF2741', '#C8475D', '#8D34A7', '#9D52B4', '#03315B', '#284F73',],
    colors17To24: [
      '#0375DE', '#2889E2', '#4F9EE7', '#13C2C2', '#36CBCB', '#5AD4D4',
      '#FFC30F', '#FFCB32', '#FFD557', '#FA8C16', '#FA9D38', '#FBAE5C', '#F14E22', '#F36842', '#F58364',
      '#BF2741', '#C8475D', '#D2687A', '#8D34A7', '#9D52B4', '#AF71C1', '#03315B', '#284F73', '#4F6F8C',
    ],
    // 小标题
    subtitleLevel1: {
      fontSize: 16,
      fontWeight: 500,
      color: 'rgba(0,0,0,0.65)'
    }
  },

  /**
   *  Echarts.js绘制的图表 部分
   */
  Echarts: {
    // 调色盘颜色列表,根据传入的数据的项数值返回UI规范中相对应的颜色列表
    colorList(num) {
      let color = []
      switch (true) {
        case num === 1:
          color = [GlobalConstAndFunc.UI.primaryColor]
          break;
        case num >= 2 && num <= 8:
          color = GlobalConstAndFunc.UI.colors2To8
          break;
        case num >= 9 && num <= 16:
          color = GlobalConstAndFunc.UI.colors9To16
          break;
        case num > 16:
          color = GlobalConstAndFunc.UI.colors17To24
          break;
        default:
          color = GlobalConstAndFunc.UI.colors17To24
          break;
      }
      return color
    },
    // 图例组件,样式设置
    legendStyle: {
      type: 'scroll',
      icon: 'circle',
      itemWidth: 10,
      itemHeight: 10,
      bottom: 20,
      itemGap: 20,
      textStyle: {
        padding: [2, 0, 0, 0],
      },
      borderColor: '#fff',
      borderRadius: 1,
      borderWidth: 5,
      pageIcons: {
        horizontal: ['path//M11.3333333,2.77859508 L11.3333333,3.83124407 C11.3333333,3.9028242 11.3007812,3.97019373 11.2473958,4.01229969 L6.1484375,7.99973405 L11.2473958,11.9871684 C11.3007812,12.0292744 11.3333333,12.0966439 11.3333333,12.168224 L11.3333333,13.220873 C11.3333333,13.3121026 11.2369792,13.3654368 11.1679687,13.3121026 L4.83854167,8.36324883 C4.609375,8.18359674 4.609375,7.81587136 4.83854167,7.6376228 L11.1679687,2.68876903 C11.2369792,2.63403129 11.3333333,2.6873655 11.3333333,2.77859508 Z', 'path://M4.66666667,2.77859508 L4.66666667,3.83124407 C4.66666667,3.9028242 4.69921875,3.97019373 4.75260417,4.01229969 L9.8515625,7.99973405 L4.75260417,11.9871684 C4.69921875,12.0292744 4.66666667,12.0966439 4.66666667,12.168224 L4.66666667,13.220873 C4.66666667,13.3121026 4.76302083,13.3654368 4.83203125,13.3121026 L11.1614583,8.36324883 C11.390625,8.18359674 11.390625,7.81587136 11.1614583,7.6376228 L4.83203125,2.68876903 C4.76302083,2.63403129 4.66666667,2.6873655 4.66666667,2.77859508 Z']
      },
      pageFormatter: '',
      pageButtonItemGap: 0,
      pageIconSize: 12,
      pageIconColor: '#0375DE',
      pageIconInactiveColor: 'rgba(0,0,0,0.25)'
    },
    // grid组件,样式设置
    gridSetStyle: {
      left: 5,
      right: 30,
      top: 25,
      bottom: 45,
      containLabel: true
    },
    // 提示框组件，样式设置
    tooltipStyle: {
      backgroundColor: 'rgba(0, 0, 0, 0.65)',
      padding: 8,
      textStyle: {
        color: '#fff',
        fontSize: 12,
        fontWeight: 400
      },
      extraCssText: 'box-shadow: 0 0 8px 0 rgba(0, 0, 0, 0.1);'
    },
    // 提示框组件，格式自定义
    tooltipFormatter(contentArr, title = '') {
      const contentStr = contentArr.join('')
      const itemLT8 = `
                        <section class="echarts-tooltip">
                          <div class="title">${title}</div>
                          <section class="content-lt8">
                            ${contentStr}
                          </section>
                        </section>
                      `
      const itemGT8 = `
                        <section class="echarts-tooltip">
                        <div class="title">${title}</div>
                        <section class="content-gt8">
                          ${contentStr}
                        </section>
                      </section>
                      `
      return contentArr.length > 8 ? itemGT8 : itemLT8
    },
    // 直角坐标系，x轴和y轴样式设置
    xAxisStyle: {
      type: 'category',
      axisLine: {
        lineStyle: {
          color: '#BFBFBF'
        }
      },
      axisLabel: {
        color: 'rgba(0, 0, 0, 0.65)',
        showMinLabel: true,
        showMaxLabel: true
      },
      axisTick: {
        show: false
      }
    },
    yAxisStyle: {
      type: 'value',
      axisLine: {
        show: false
      },
      axisTick: {
        show: false
      },
      axisLabel: {
        color: 'rgba(0, 0, 0, 0.65)',
      },
      splitLine: {
        lineStyle: {
          type: 'dotted',
          color: '#F0F0F0'
        }
      }
    },
  },

  // Element-ui 部分
  Element: {
    /**表格表头不换行,溢出则隐藏，鼠标悬浮显示完整文本：
     * 前两个参数见element - ui中table组件的render-header方法
     * @param {boolean} isLastHeader 是否是表头中最后一个th
     */
    renderTableHeader(h, {
      column,
      column: {
        width,
        realWidth,
        minWidth,
        label,
        sortable
      },
      $index
    }, isLastHeader = false) {
      // cellContentWidth 为当前表头单元格th的实际宽度(padding+width)
      const cellContentWidth = realWidth ? realWidth : width
      // cellWidth 为当前表头单元格th的实际宽度(width)
      let cellWidth

      // 根据UI规范，表头两侧要内缩进40px，所以两侧的th>div.cell分别在css中修改了相应方向padding的值为40,其余的th>div.cell的左右padding各10
      if ($index === 0 || isLastHeader) {
        cellWidth = cellContentWidth - 20 - 10
      } else {
        cellWidth = cellContentWidth - 10 - 10
      }

      const length = label.length
      // labelWidth为表头label完全展示需要的实际宽度。14是字体大小,如果支持排序，还要加上排序图标的宽度17
      let labelWidth = sortable ? 14 * length + 17 : 14 * length

      if (labelWidth > cellWidth) {
        // showLength 为实际可以展示几个字符，不包括省略号（占14px）和排序图标的宽度17
        let showLength = sortable ? Math.floor((cellWidth - 14 - 17) / 14) : Math.floor((cellWidth - 14) / 14)
        const showLabel = label.slice(0, showLength) + '...'
        return h('el-tooltip', {
          props: {
            content: label,
            placement: 'top'
          }
        }, [
          h('span', [showLabel])
        ])
      } else {
        return h('span', [label])
      }
    },
  },

  // 不同环境下ERP数据看板模块下页面的接口地址
  api() {
    let api = location.protocol
    switch (location.origin) {
      case 'http://erp.gshopper.com':
      case 'https://erp.gshopper.com':
        api += '//insight.gshopper.com/insight-backend/'
        break;
      case 'http://erp.gshopper.prod.com':
        api += '//insight.gshopper.prod.com/insight-backend/'
        break;
      case 'http://erpstage.gshopper.com':
      case 'https://erpstage.gshopper.com':
        api += '//insightprod.gshopper.com/insight-backend/'
        break;
      default:
        api += '//insight.gshopper.stage.com/insight-backend/'
        break;
    }
    return api
  },

})