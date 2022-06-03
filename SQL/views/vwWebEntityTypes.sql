USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebEntityTypes]    Script Date: 3/06/2022 2:22:28 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO


ALTER VIEW [dbo].[vwWebEntityTypes]
AS
SELECT        ET_ID, ET_Code, ET_Desc
FROM            dbo.EntityTypes

GO


