USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebEntityTypes]    Script Date: 29/10/2021 1:14:59 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO


ALTER VIEW [dbo].[vwWebEntityTypes]
AS
SELECT        ET_ID, ET_Code, ET_Desc
FROM            dbo.EntityTypes

GO


